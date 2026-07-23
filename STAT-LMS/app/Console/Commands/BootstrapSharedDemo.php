<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\SharedDemoLifecycleService;
use Illuminate\Console\Command;
use Throwable;

class BootstrapSharedDemo extends Command
{
    protected $signature = 'demo:bootstrap-shared {--force : Confirm initialization of an empty shared demo}';

    protected $description = 'Initialize canonical shared-demo data when the application tables are empty';

    public function handle(SharedDemoLifecycleService $lifecycle): int
    {
        if (! $this->option('force')) {
            $this->error('Refusing to initialize shared-demo data without --force.');

            return self::FAILURE;
        }

        try {
            $result = $lifecycle->bootstrap();
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $action = $result['seeded'] ? 'Initialized' : 'Skipped initialization; canonical data already exists.';
        $this->info($action);
        $this->line(json_encode($result['counts'], JSON_THROW_ON_ERROR));

        return self::SUCCESS;
    }
}
