<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\SharedDemoLifecycleService;
use Illuminate\Console\Command;
use Throwable;

class HealthSharedDemo extends Command
{
    protected $signature = 'demo:health-shared {--json : Render machine-readable health output}';

    protected $description = 'Report shared-demo database, lifecycle, and material storage health';

    public function handle(SharedDemoLifecycleService $lifecycle): int
    {
        try {
            $health = $lifecycle->health();
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($this->option('json')) {
            $this->line(json_encode($health, JSON_THROW_ON_ERROR));
        } else {
            $this->table(
                ['Check', 'Value'],
                [
                    ['Database', $health['database']['connected'] ? 'connected' : 'unavailable'],
                    ['Runtime status', $health['runtime_state']['status'] ?? 'unavailable'],
                    ['Maintenance', ($health['runtime_state']['maintenance'] ?? true) ? 'active' : 'inactive'],
                    ['Material disk', $health['material_storage']['available'] ? 'available' : 'unavailable'],
                    ['Upload bytes', (string) $health['material_storage']['upload_bytes']],
                ],
            );
        }

        return $health['healthy'] ? self::SUCCESS : self::FAILURE;
    }
}
