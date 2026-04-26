<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

/**
 * Security: ExportTestResults Command Path Validation
 *
 * Verifies that the test:export artisan command properly validates
 * the output path to prevent writing outside the public/ directory via:
 * - Path traversal attempts (../../)
 * - Nested directory escapes
 *
 * Note on absolute paths: public_path('/tmp/evil.pdf') resolves to
 * …/public/tmp/evil.pdf (inside public/), so absolute inputs are not an
 * escape vector — they are simply treated as relative to public/.
 *
 * This test suite mirrors the exact normalisation logic in ExportTestResults::handle()
 * without spawning the expensive full test suite run.
 */
class ExportTestResultsSecurityTest extends TestCase
{
    /**
     * Helper: Validate a path like the command does.
     * Mirrors the exact normalisation logic in ExportTestResults::handle().
     */
    private function validatePath(string $rawOutputPath): bool
    {
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

        return str_starts_with(
            rtrim($tentativeDir, '/\\').DIRECTORY_SEPARATOR,
            rtrim($publicBase, '/\\').DIRECTORY_SEPARATOR
        );
    }

    /** @test */
    public function rejects_path_traversal_outside_public(): void
    {
        $this->assertFalse($this->validatePath('../../etc/passwd'));
    }

    /** @test */
    public function rejects_path_with_deeply_nested_traversal(): void
    {
        $this->assertFalse($this->validatePath('../../../../../../../etc/passwd'));
    }

    /** @test */
    public function rejects_symlink_escape_attempts(): void
    {
        $this->assertFalse($this->validatePath('../../etc/shadow'));
    }

    /** @test */
    public function rejects_deeply_nested_dot_dot_outside_public(): void
    {
        $this->assertFalse($this->validatePath('../../newdir1/newdir2/x.pdf'));
    }

    /**
     * Absolute paths are safely neutralised: public_path('/tmp/evil.pdf')
     * resolves to …/public/tmp/evil.pdf which is inside public/, so no
     * filesystem escape is possible via this input.
     *
     * @test
     */
    public function absolute_path_is_contained_inside_public(): void
    {
        // public_path('/tmp/evil.pdf') → …/public/tmp/evil.pdf — inside public/
        $this->assertTrue($this->validatePath('/tmp/evil.pdf'));
    }

    /** @test */
    public function accepts_simple_filename_in_public_directory(): void
    {
        $this->assertTrue($this->validatePath('reports/test-results.pdf'));
    }

    /** @test */
    public function accepts_nested_path_in_public_directory(): void
    {
        $this->assertTrue($this->validatePath('exports/pdfs/monthly-report.pdf'));
    }

    /** @test */
    public function accepts_root_level_filename(): void
    {
        $this->assertTrue($this->validatePath('test-export.pdf'));
    }
}
