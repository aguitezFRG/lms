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

        foreach ($expired as $event) {
            $event->updateQuietly(['status' => 'revoked', 'completed_at' => now()]);

            $hasOtherActive = \App\Models\MaterialAccessEvents::where('rr_material_id', $event->rr_material_id)
                ->where('id', '!=', $event->id)
                ->whereIn('status', ['approved'])
                ->whereNull('completed_at')
                ->exists();

            if (! $hasOtherActive) {
                $event->material?->updateQuietly(['is_available' => true]);
            }

            $event->user?->notify(new RequestStatusChanged($event));
        }

        $this->info("Expired {$expired->count()} digital access event(s).");
    }
}
