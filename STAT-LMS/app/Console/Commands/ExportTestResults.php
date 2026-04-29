<?php

namespace App\Console\Commands;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class ExportTestResults extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:export
                            {--path=reports/test-results.pdf : The output path for the PDF}
                            {--failures-only : Only include failed and errored test cases in the report}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run application tests and export the results to a professional PDF report';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $xmlPath = storage_path('app/junit.xml');
        $rawOutputPath = $this->option('path');
        $failuresOnly = $this->option('failures-only');
        $basePath = base_path();

        // Normalise dot-dot segments without touching the filesystem.
        $normalisePath = static function (string $path): string {
            $sep = DIRECTORY_SEPARATOR;
            $parts = [];
            foreach (explode($sep, str_replace(['/', '\\'], $sep, $path)) as $part) {
                if ($part === '' || $part === '.') {
                    continue;
                }
                if ($part === '..') {
                    array_pop($parts);
                } else {
                    $parts[] = $part;
                }
            }

            return $sep.implode($sep, $parts);
        };

        $publicBase = $normalisePath(public_path());
        $tentativeDir = $normalisePath(dirname(public_path($rawOutputPath)));
        $fullOutputPath = $tentativeDir.DIRECTORY_SEPARATOR.basename($rawOutputPath);

        if (! str_starts_with(
            rtrim($tentativeDir, '/\\').DIRECTORY_SEPARATOR,
            rtrim($publicBase, '/\\').DIRECTORY_SEPARATOR
        )) {
            $this->error('Invalid output path: path must resolve inside the public/ directory.');

            return self::FAILURE;
        }

        \Illuminate\Support\Facades\File::ensureDirectoryExists($tentativeDir);
        $outputPath = $rawOutputPath;

        // Clean up any stale XML from a previous run
        File::delete($xmlPath);

        $this->info('🚀 Running tests...');

        // 1. Run PHPUnit with an absolute path and explicit working directory.
        //    We intentionally do NOT use ->throw() because test failures cause
        //    a non-zero exit code — that is expected, not an error we want to
        //    abort on. What matters is whether the XML was written.
        $process = Process::path($basePath)
            ->run("php vendor/bin/phpunit --log-junit \"{$xmlPath}\" --colors=never");

        // 2. Verify the XML was actually produced and is non-empty
        if (! File::exists($xmlPath) || File::size($xmlPath) === 0) {
            $this->error('PHPUnit did not produce a JUnit XML file.');
            $this->line('');
            $this->line('--- PHPUnit stdout ---');
            $this->line($process->output());
            $this->line('--- PHPUnit stderr ---');
            $this->line($process->errorOutput());

            return 1;
        }

        $this->info('📊 Parsing results...');

        // 3. Parse the XML — disable libxml errors so we can handle them ourselves
        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($xmlPath);

        if ($xml === false) {
            $errors = implode(', ', array_map(fn ($e) => $e->message, libxml_get_errors()));
            libxml_clear_errors();
            $this->error("Failed to parse JUnit XML: {$errors}");

            return 1;
        }

        libxml_clear_errors();

        // 4. Build data array — find the top-level <testsuite> that holds totals
        //    The root element is <testsuites>; its first child is the suite with
        //    the aggregate stats.
        $mainSuite = ($xml->getName() === 'testsuites' && isset($xml->testsuite[0]))
            ? $xml->testsuite[0]
            : $xml;

        $data = [
            'date' => now()->toDayDateTimeString(),
            'total_tests' => (int) ($mainSuite['tests'] ?? 0),
            'failures' => (int) ($mainSuite['failures'] ?? 0),
            'errors' => (int) ($mainSuite['errors'] ?? 0),
            'time' => round((float) ($mainSuite['time'] ?? 0), 2),
            'failures_only' => $failuresOnly,
            'suites' => [],
        ];

        // 5. Gather every <testcase> node regardless of nesting depth
        $testCases = $xml->xpath('//testcase');
        $groupedSuites = [];

        foreach ($testCases as $case) {
            $className = (string) $case['class'];

            if (! isset($groupedSuites[$className])) {
                $groupedSuites[$className] = [
                    'name' => class_basename($className),
                    'tests' => 0,
                    'failures' => 0,
                    'cases' => [],
                ];
            }

            $groupedSuites[$className]['tests']++;

            $status = 'passed';
            $message = '';

            if (isset($case->failure)) {
                $status = 'failed';
                $message = (string) $case->failure;
                $groupedSuites[$className]['failures']++;
            } elseif (isset($case->error)) {
                $status = 'error';
                $message = (string) $case->error;
                $groupedSuites[$className]['failures']++;
            } elseif (isset($case->skipped)) {
                $status = 'skipped';
            }

            $groupedSuites[$className]['cases'][] = [
                'name' => (string) $case['name'],
                'class' => $className,
                'time' => round((float) ($case['time'] ?? 0), 3),
                'status' => $status,
                'message' => $message,
            ];
        }

        // 6. If --failures-only, strip passing cases and empty suites
        if ($failuresOnly) {
            foreach ($groupedSuites as $className => &$suite) {
                $suite['cases'] = array_values(
                    array_filter(
                        $suite['cases'],
                        fn ($case) => in_array($case['status'], ['failed', 'error'])
                    )
                );
            }
            unset($suite);

            // Drop suites that have no failing cases left
            $groupedSuites = array_filter(
                $groupedSuites,
                fn ($suite) => count($suite['cases']) > 0
            );
        }

        $data['suites'] = array_values($groupedSuites);

        $this->info('📄 Generating PDF...');

        // 7. Generate PDF
        $pdf = Pdf::loadView('reports.test-results', $data)
            ->setPaper('a4', 'portrait');

        $pdf->save($fullOutputPath);

        // 8. Clean up the temporary XML
        File::delete($xmlPath);

        $passed = $data['total_tests'] - $data['failures'] - $data['errors'];

        $this->info("✅ Report saved to: {$fullOutputPath}");
        $this->table(
            ['Total', 'Passed', 'Failed', 'Errors', 'Duration'],
            [[
                $data['total_tests'],
                $passed,
                $data['failures'],
                $data['errors'],
                $data['time'].'s',
            ]]
        );

        if ($failuresOnly && ($data['failures'] + $data['errors']) === 0) {
            $this->info('🎉 All tests passed — nothing to show in failures-only mode.');
        } elseif ($process->failed()) {
            $this->warn('⚠  Some tests failed — see the PDF for details.');
        }

        return 0;
    }
}
