<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\SharedDemoLifecycleService;
use Illuminate\Console\Command;
use Throwable;

class ResetSharedDemo extends Command
{
    protected $signature = 'demo:reset-shared {--force : Confirm reset of mutable shared-demo data} {--idempotency-key= : Required reset idempotency key}';

    protected $description = 'Reset shared-demo data to the canonical seed and remove uploads';

    public function handle(SharedDemoLifecycleService $lifecycle): int
    {
        if (! $this->option('force')) {
            $this->error('Refusing to reset shared-demo data without --force.');

            return self::FAILURE;
        }

        $idempotencyKey = $this->option('idempotency-key');
        if (! is_string($idempotencyKey) || trim($idempotencyKey) === '') {
            $this->error('The --idempotency-key option is required.');

            return self::FAILURE;
        }

        try {
            $result = $lifecycle->reset(trim($idempotencyKey));
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info($result['skipped'] ? 'Skipped duplicate reset.' : 'Reset completed.');
        $this->line(json_encode($result, JSON_THROW_ON_ERROR));

        return $result['status'] === 'ready' ? self::SUCCESS : self::FAILURE;
    }
}
