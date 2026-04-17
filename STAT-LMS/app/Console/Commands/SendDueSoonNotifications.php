<?php

namespace App\Console\Commands;

use App\Models\MaterialAccessEvents;
use App\Notifications\BorrowDueSoon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendDueSoonNotifications extends Command
{
    protected $signature = 'notifications:due-soon';

    protected $description = 'Send due-soon notifications for borrows due in 1 or 3 days';

    public function handle(): void
    {
        $thresholds = [3, 1];

        foreach ($thresholds as $days) {
            $targetDate = now()->addDays($days)->toDateString();

            $events = MaterialAccessEvents::with(['user', 'material.parent'])
                ->where('event_type', 'borrow')
                ->where('status', 'approved')
                ->whereNull('returned_at')
                ->whereNull('completed_at')
                ->whereDate('due_at', $targetDate)
                ->get();

            foreach ($events as $event) {
                if (! $event->user) {
                    continue;
                }

                // Suppress duplicate: skip if user already got this exact
                // notification type for this event today
                $alreadyNotified = DB::table('notifications')
                    ->where('notifiable_type', get_class($event->user))
                    ->where('notifiable_id', $event->user->id)
                    ->whereRaw("JSON_EXTRACT(data, '$.type') = 'borrow_due_soon'")
                    ->whereRaw("JSON_EXTRACT(data, '$.event_id') = ?", [$event->id])
                    ->whereRaw("JSON_EXTRACT(data, '$.days_until_due') = ?", [$days])
                    ->whereDate('created_at', today())
                    ->exists();

                if ($alreadyNotified) {
                    continue;
                }

                $event->user->notify(new BorrowDueSoon($event, $days));
            }

            $this->info("Processed {$events->count()} borrows due in {$days} day(s).");
        }
    }
}
