<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SharedDemoRuntimeState;
use Database\Seeders\DemoDatabaseSeeder;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class SharedDemoLifecycleService
{
    private const ADVISORY_LOCK_KEY = 468_667_133;

    /** @var list<string> */
    private const CANONICAL_TABLES = [
        'users',
        'rr_material_parents',
        'rr_materials',
        'material_access_events',
    ];

    /** @var list<string> */
    private const RESET_TABLES = [
        'repository_change_logs',
        'material_access_events',
        'notifications',
        'password_reset_tokens',
        'sessions',
        'jobs',
        'job_batches',
        'failed_jobs',
        'cache',
        'rr_materials',
        'rr_material_parents',
        'users',
    ];

    /**
     * @return array{seeded: bool, counts: array<string, int>}
     */
    public function bootstrap(): array
    {
        $this->requireServerRuntime();

        return $this->withExclusiveLock(function (): array {
            if (! $this->canonicalTablesAreEmpty()) {
                return [
                    'seeded' => false,
                    'counts' => $this->canonicalCounts(),
                ];
            }

            $counts = DB::transaction(function (): array {
                $state = $this->runtimeState();
                $state->forceFill([
                    'status' => 'bootstrapping',
                    'maintenance' => true,
                    'last_error' => null,
                ])->save();

                app(DemoDatabaseSeeder::class)->run();
                $counts = $this->canonicalCounts();

                $state->forceFill([
                    'status' => 'ready',
                    'maintenance' => false,
                    'last_bootstrapped_at' => now(),
                    'canonical_counts' => $counts,
                    'last_error' => null,
                ])->save();

                return $counts;
            });

            return [
                'seeded' => true,
                'counts' => $counts,
            ];
        });
    }

    /**
     * @return array{skipped: bool, status: string, counts: array<string, int>, deleted_uploads: int}
     */
    public function reset(string $idempotencyKey): array
    {
        $this->requireServerRuntime();

        return $this->withExclusiveLock(function () use ($idempotencyKey): array {
            $state = $this->runtimeState();

            if ($state->last_idempotency_key === $idempotencyKey) {
                $deletedUploads = $this->completeStorageCleanup($state);

                return [
                    'skipped' => true,
                    'status' => $state->fresh()->status,
                    'counts' => $this->canonicalCounts(),
                    'deleted_uploads' => $deletedUploads,
                ];
            }

            if ($state->maintenance) {
                throw new RuntimeException('A shared-demo lifecycle operation is already in progress.');
            }

            $startedAt = hrtime(true);

            try {
                $counts = DB::transaction(function () use ($idempotencyKey, $startedAt): array {
                    $state = $this->runtimeState();
                    $state->forceFill([
                        'status' => 'resetting',
                        'maintenance' => true,
                        'last_error' => null,
                    ])->save();

                    foreach (self::RESET_TABLES as $table) {
                        DB::table($table)->delete();
                    }

                    app(DemoDatabaseSeeder::class)->run();
                    $counts = $this->canonicalCounts();

                    $state->forceFill([
                        'status' => 'cleaning_storage',
                        'maintenance' => true,
                        'last_idempotency_key' => $idempotencyKey,
                        'last_reset_at' => now(),
                        'last_duration_ms' => $this->elapsedMilliseconds($startedAt),
                        'canonical_counts' => $counts,
                        'last_error' => null,
                    ])->save();

                    return $counts;
                });
            } catch (Throwable $exception) {
                $this->markDatabaseFailure($exception);

                throw $exception;
            }

            $deletedUploads = $this->completeStorageCleanup($this->runtimeState());

            return [
                'skipped' => false,
                'status' => $this->runtimeState()->status,
                'counts' => $counts,
                'deleted_uploads' => $deletedUploads,
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function health(): array
    {
        $this->requireServerRuntime();

        $databaseConnected = false;
        $state = null;
        $counts = [];

        try {
            DB::connection()->getPdo();
            $databaseConnected = true;
            $state = $this->runtimeState();
            $counts = $this->canonicalCounts();
        } catch (Throwable) {
            // Health output intentionally avoids connection details and credentials.
        }

        $storage = $this->storageHealth();
        $maintenance = $state?->maintenance ?? true;

        return [
            'healthy' => $databaseConnected
                && $storage['available']
                && ! $maintenance
                && $state?->status === 'ready',
            'database' => ['connected' => $databaseConnected],
            'runtime_state' => $state === null ? null : [
                'status' => $state->status,
                'maintenance' => $state->maintenance,
                'last_idempotency_key' => $state->last_idempotency_key,
                'last_bootstrapped_at' => $state->last_bootstrapped_at?->toIso8601String(),
                'last_reset_at' => $state->last_reset_at?->toIso8601String(),
                'last_duration_ms' => $state->last_duration_ms,
            ],
            'canonical_counts' => $counts,
            'material_storage' => $storage,
        ];
    }

    public function requireServerRuntime(): void
    {
        if (! config('demo.enabled') || config('demo.runtime') !== 'server') {
            throw new RuntimeException('Shared-demo lifecycle commands require DEMO_MODE=true and DEMO_RUNTIME=server.');
        }
    }

    /**
     * @return array{available: bool, disk: string, upload_bytes: int, warning: bool, limit_exceeded: bool}
     */
    private function storageHealth(): array
    {
        $diskName = (string) config('demo.material_disk', 'local');

        try {
            $uploadBytes = $this->uploadBytes($this->materialDisk());
            $warningThreshold = (int) config('demo.shared_upload_warning_bytes');
            $limit = (int) config('demo.max_shared_upload_bytes');

            return [
                'available' => true,
                'disk' => $diskName,
                'upload_bytes' => $uploadBytes,
                'warning' => $uploadBytes >= $warningThreshold,
                'limit_exceeded' => $uploadBytes > $limit,
            ];
        } catch (Throwable) {
            return [
                'available' => false,
                'disk' => $diskName,
                'upload_bytes' => 0,
                'warning' => false,
                'limit_exceeded' => false,
            ];
        }
    }

    private function completeStorageCleanup(SharedDemoRuntimeState $state): int
    {
        try {
            $disk = $this->materialDisk();
            $uploads = $disk->allFiles('uploads');

            if ($uploads !== []) {
                $disk->delete($uploads);
            }

            $state->forceFill([
                'status' => 'ready',
                'maintenance' => false,
                'last_error' => null,
            ])->save();

            return count($uploads);
        } catch (Throwable $exception) {
            $state->forceFill([
                'status' => 'degraded',
                'maintenance' => false,
                'last_error' => $this->safeErrorMessage($exception),
            ])->save();

            return 0;
        }
    }

    private function markDatabaseFailure(Throwable $exception): void
    {
        $state = $this->runtimeState();
        $state->forceFill([
            'status' => 'failed',
            'maintenance' => false,
            'last_error' => $this->safeErrorMessage($exception),
        ])->save();
    }

    private function runtimeState(): SharedDemoRuntimeState
    {
        return SharedDemoRuntimeState::query()->firstOrCreate(
            ['id' => 1],
            [
                'status' => 'ready',
                'maintenance' => false,
            ],
        );
    }

    private function canonicalTablesAreEmpty(): bool
    {
        foreach (self::CANONICAL_TABLES as $table) {
            if (DB::table($table)->exists()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, int>
     */
    private function canonicalCounts(): array
    {
        $counts = [];

        foreach (self::CANONICAL_TABLES as $table) {
            $counts[$table] = DB::table($table)->count();
        }

        return $counts;
    }

    private function materialDisk(): FilesystemAdapter
    {
        return Storage::disk((string) config('demo.material_disk', 'local'));
    }

    private function uploadBytes(FilesystemAdapter $disk): int
    {
        return array_sum(array_map($disk->size(...), $disk->allFiles('uploads')));
    }

    private function elapsedMilliseconds(int $startedAt): int
    {
        return (int) floor((hrtime(true) - $startedAt) / 1_000_000);
    }

    private function safeErrorMessage(Throwable $exception): string
    {
        return mb_substr($exception::class, 0, 500);
    }

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    private function withExclusiveLock(callable $callback): mixed
    {
        if (DB::getDriverName() === 'pgsql') {
            $lock = DB::selectOne('SELECT pg_try_advisory_lock(?) AS acquired', [self::ADVISORY_LOCK_KEY]);

            if (! in_array($lock?->acquired, [true, 1, '1', 't', 'true'], true)) {
                throw new RuntimeException('Another shared-demo lifecycle operation is already running.');
            }

            try {
                return $callback();
            } finally {
                DB::selectOne('SELECT pg_advisory_unlock(?)', [self::ADVISORY_LOCK_KEY]);
            }
        }

        $lock = Cache::lock('shared-demo-lifecycle', 300);

        if (! $lock->get()) {
            throw new RuntimeException('Another shared-demo lifecycle operation is already running.');
        }

        try {
            return $callback();
        } finally {
            $lock->release();
        }
    }
}
