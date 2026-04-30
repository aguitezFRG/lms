<?php

namespace App\Console\Commands;

use App\Models\MaterialAccessEvents;
use App\Notifications\RequestStatusChanged;
use Illuminate\Console\Command;

class ExpireDigitalAccess extends Command
{
    protected $signature = 'access:expire-digital';

    protected $description = 'Revoke expired digital access events and restore material availability';

    public function handle(): void
    {
        $expired = MaterialAccessEvents::with(['user', 'material'])
            ->where('event_type', 'request')
            ->where('status', 'approved')
            ->whereNull('completed_at')
            ->where('due_at', '<=', now())
            ->whereHas('material', fn ($q) => $q->where('is_digital', true))
            ->get();

        $now = now();
        $materialIds = $expired->pluck('rr_material_id')->unique()->values();

        $activeCountsByMaterial = MaterialAccessEvents::whereIn('rr_material_id', $materialIds)
            ->where('status', 'approved')
            ->whereNull('completed_at')
            ->selectRaw('rr_material_id, COUNT(*) as aggregate')
            ->groupBy('rr_material_id')
            ->pluck('aggregate', 'rr_material_id')
            ->map(fn ($count) => (int) $count);

        foreach ($expired as $event) {
            $event->updateQuietly(['status' => 'revoked', 'completed_at' => $now]);

            $materialId = $event->rr_material_id;
            $remainingActive = max(0, (int) ($activeCountsByMaterial[$materialId] ?? 0) - 1);
            $activeCountsByMaterial[$materialId] = $remainingActive;

            if ($remainingActive === 0) {
                $event->material?->updateQuietly(['is_available' => true]);
            }

            $event->user?->notify(new RequestStatusChanged($event));
        }

        $this->info("Expired {$expired->count()} digital access event(s).");
    }
}
