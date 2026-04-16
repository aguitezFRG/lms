<?php

namespace App\Listeners;

use App\Models\MaterialAccessEvents;
use App\Notifications\BorrowDueSoon;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\DB;

class SendDueSoonOnLogin
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        // Only relevant for students and faculty who can borrow
        if (! in_array($user->role, ['student', 'faculty'])) {
            return;
        }

        $thresholds = [3, 1];

        foreach ($thresholds as $days) {
            $targetDate = now()->addDays($days)->toDateString();

            $borrows = MaterialAccessEvents::with(['material.parent'])
                ->where('user_id', $user->id)
                ->where('event_type', 'borrow')
                ->where('status', 'approved')
                ->whereNull('returned_at')
                ->whereNull('completed_at')
                ->whereDate('due_at', $targetDate)
                ->get();

            foreach ($borrows as $borrow) {
                // Suppress duplicate: already notified today for this event + threshold
                $alreadyNotified = DB::table('notifications')
                    ->where('notifiable_type', get_class($user))
                    ->where('notifiable_id', $user->id)
                    ->whereRaw("JSON_EXTRACT(data, '$.type') = 'borrow_due_soon'")
                    ->whereRaw("JSON_EXTRACT(data, '$.event_id') = ?", [$borrow->id])
                    ->whereRaw("JSON_EXTRACT(data, '$.days_until_due') = ?", [$days])
                    ->whereDate('created_at', today())
                    ->exists();

                if ($alreadyNotified) {
                    continue;
                }

                $user->notify(new BorrowDueSoon($borrow, $days));
            }
        }
    }
}
