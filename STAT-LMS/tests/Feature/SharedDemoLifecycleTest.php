<?php

namespace Tests\Feature;

use App\Models\RrMaterialParents;
use App\Models\RrMaterials;
use App\Models\SharedDemoRuntimeState;
use App\Services\PdfNormalizationService;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class SharedDemoLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private const MATERIAL_DISK = 'shared-demo-test';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(self::MATERIAL_DISK);
        config([
            'demo.material_disk' => self::MATERIAL_DISK,
            'demo.access_enforced' => false,
        ]);
    }

    #[Test]
    public function lifecycle_commands_refuse_every_runtime_except_the_enabled_server_demo(): void
    {
        $commands = [
            ['demo:bootstrap-shared', ['--force' => true]],
            ['demo:reset-shared', ['--force' => true, '--idempotency-key' => 'guard-test']],
            ['demo:health-shared', ['--json' => true]],
        ];

        foreach ([
            ['enabled' => false, 'runtime' => 'server'],
            ['enabled' => true, 'runtime' => 'browser'],
        ] as $runtime) {
            config([
                'demo.enabled' => $runtime['enabled'],
                'demo.runtime' => $runtime['runtime'],
            ]);

            foreach ($commands as [$command, $arguments]) {
                $this->assertSame(Command::FAILURE, Artisan::call($command, $arguments));
                $this->assertStringContainsString(
                    'Shared-demo lifecycle commands require DEMO_MODE=true and DEMO_RUNTIME=server.',
                    Artisan::output(),
                );
            }
        }
    }

    #[Test]
    public function sqlite_bootstrap_and_reset_are_idempotent_and_delete_only_upload_objects(): void
    {
        $this->enableServerRuntime();

        $this->assertSame(
            Command::SUCCESS,
            Artisan::call('demo:bootstrap-shared', ['--force' => true]),
        );

        $initialCounts = $this->canonicalCounts();
        $seedFiles = Storage::disk(self::MATERIAL_DISK)->allFiles('seed');

        $this->assertNotEmpty($seedFiles);
        $this->assertSame('ready', SharedDemoRuntimeState::findOrFail(1)->status);

        $this->assertSame(
            Command::SUCCESS,
            Artisan::call('demo:bootstrap-shared', ['--force' => true]),
        );
        $this->assertStringContainsString(
            'Skipped initialization; canonical data already exists.',
            Artisan::output(),
        );
        $this->assertSame($initialCounts, $this->canonicalCounts());

        Storage::disk(self::MATERIAL_DISK)->put('seed/custom-preserved.pdf', 'canonical');
        Storage::disk(self::MATERIAL_DISK)->put('uploads/first.pdf', 'mutable');

        $this->assertSame(
            Command::SUCCESS,
            Artisan::call('demo:reset-shared', [
                '--force' => true,
                '--idempotency-key' => 'daily-reset-2026-07-23',
            ]),
        );

        $firstReset = $this->lastJsonObject(Artisan::output());

        $this->assertFalse($firstReset['skipped']);
        $this->assertSame(1, $firstReset['deleted_uploads']);
        $this->assertSame($initialCounts, $this->canonicalCounts());
        Storage::disk(self::MATERIAL_DISK)->assertMissing('uploads/first.pdf');
        Storage::disk(self::MATERIAL_DISK)->assertExists('seed/custom-preserved.pdf');

        Storage::disk(self::MATERIAL_DISK)->put('uploads/retry.pdf', 'retry');

        $this->assertSame(
            Command::SUCCESS,
            Artisan::call('demo:reset-shared', [
                '--force' => true,
                '--idempotency-key' => 'daily-reset-2026-07-23',
            ]),
        );

        $duplicateReset = $this->lastJsonObject(Artisan::output());

        $this->assertTrue($duplicateReset['skipped']);
        $this->assertSame(1, $duplicateReset['deleted_uploads']);
        $this->assertSame($initialCounts, $this->canonicalCounts());
        Storage::disk(self::MATERIAL_DISK)->assertMissing('uploads/retry.pdf');
        Storage::disk(self::MATERIAL_DISK)->assertExists('seed/custom-preserved.pdf');

        $state = SharedDemoRuntimeState::findOrFail(1);

        $this->assertSame('ready', $state->status);
        $this->assertFalse($state->maintenance);
        $this->assertSame('daily-reset-2026-07-23', $state->last_idempotency_key);
    }

    #[Test]
    public function health_command_emits_healthy_json_for_a_ready_runtime(): void
    {
        $this->enableServerRuntime();
        Artisan::call('demo:bootstrap-shared', ['--force' => true]);

        $exitCode = Artisan::call('demo:health-shared', ['--json' => true]);
        $health = $this->lastJsonObject(Artisan::output());

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertTrue($health['healthy']);
        $this->assertTrue($health['database']['connected']);
        $this->assertSame('ready', $health['runtime_state']['status']);
        $this->assertFalse($health['runtime_state']['maintenance']);
        $this->assertTrue($health['material_storage']['available']);
    }

    #[Test]
    public function health_command_reports_a_degraded_runtime_as_unhealthy(): void
    {
        $this->enableServerRuntime();
        Artisan::call('demo:bootstrap-shared', ['--force' => true]);

        SharedDemoRuntimeState::findOrFail(1)->forceFill([
            'status' => 'degraded',
            'maintenance' => false,
            'last_error' => RuntimeException::class,
        ])->save();

        $exitCode = Artisan::call('demo:health-shared', ['--json' => true]);
        $health = $this->lastJsonObject(Artisan::output());

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertFalse($health['healthy']);
        $this->assertSame('degraded', $health['runtime_state']['status']);
    }

    #[Test]
    public function server_upload_quota_counts_only_uploads_and_cleans_the_rejected_object(): void
    {
        $this->enableServerRuntime();

        $pdf = "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n%%EOF";
        config(['demo.max_shared_upload_bytes' => strlen($pdf) + 1]);

        Storage::disk(self::MATERIAL_DISK)->put('seed/repository/large-seed.pdf', str_repeat($pdf, 10));
        Storage::disk(self::MATERIAL_DISK)->put('uploads/repository/accepted.pdf', $pdf);

        $normalizer = $this->mock(PdfNormalizationService::class);
        $normalizer->shouldReceive('normalize')->twice();

        $acceptedParent = RrMaterialParents::factory()->create();
        $accepted = RrMaterials::factory()->create([
            'material_parent_id' => $acceptedParent->id,
            'is_digital' => true,
            'is_available' => true,
            'file_name' => 'uploads/repository/accepted.pdf',
        ]);

        $this->assertNotNull($accepted->id);
        Storage::disk(self::MATERIAL_DISK)->assertExists('seed/repository/large-seed.pdf');
        Storage::disk(self::MATERIAL_DISK)->assertExists('uploads/repository/accepted.pdf');

        Storage::disk(self::MATERIAL_DISK)->put('uploads/repository/rejected.pdf', $pdf);
        $rejectedParent = RrMaterialParents::factory()->create();

        try {
            RrMaterials::factory()->create([
                'material_parent_id' => $rejectedParent->id,
                'is_digital' => true,
                'is_available' => true,
                'file_name' => 'uploads/repository/rejected.pdf',
            ]);

            $this->fail('The aggregate shared-demo upload quota should reject the second upload.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'The shared demo upload quota has been reached. Please wait for the next daily reset.',
                $exception->getMessage(),
            );
        }

        Storage::disk(self::MATERIAL_DISK)->assertMissing('uploads/repository/rejected.pdf');
        Storage::disk(self::MATERIAL_DISK)->assertExists('uploads/repository/accepted.pdf');
        Storage::disk(self::MATERIAL_DISK)->assertExists('seed/repository/large-seed.pdf');
    }

    private function enableServerRuntime(): void
    {
        config([
            'demo.enabled' => true,
            'demo.runtime' => 'server',
            'demo.material_disk' => self::MATERIAL_DISK,
            'demo.access_enforced' => false,
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function canonicalCounts(): array
    {
        return collect([
            'users',
            'rr_material_parents',
            'rr_materials',
            'material_access_events',
        ])->mapWithKeys(fn (string $table): array => [
            $table => DB::table($table)->count(),
        ])->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function lastJsonObject(string $output): array
    {
        foreach (array_reverse(preg_split('/\R/', trim($output)) ?: []) as $line) {
            $decoded = json_decode($line, true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        $this->fail("Command output did not contain a JSON object:\n{$output}");
    }
}
