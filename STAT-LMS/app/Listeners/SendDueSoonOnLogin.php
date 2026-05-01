<?php

namespace App\Listeners;

use App\Enums\UserRole;
use App\Models\MaterialAccessEvents;
use App\Models\User;
use App\Notifications\BorrowDueSoon;
use App\Notifications\BorrowOverdue;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\DB;

class SendDueSoonOnLogin
{
    public function handle(Login $event): void
    {
        /** @var User $user */
        $user = $event->user;

        if (! in_array($user->role, [UserRole::STUDENT, UserRole::FACULTY], true)) {
            return;
        }

        $sessionId = session()->getId();

        $this->sendDueSoonNotifications($user, $sessionId);
        $this->sendOverdueNotifications($user, $sessionId);
    }

    protected function sendDueSoonNotifications(User $user, string $sessionId): void
    {
        foreach ([3, 1] as $days) {
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
                $alreadyNotified = DB::table('notifications')
                    ->where('notifiable_type', get_class($user))
                    ->where('notifiable_id', $user->id)
                    ->whereRaw("JSON_EXTRACT(data, '$.type') = 'borrow_due_soon'")
                    ->whereRaw("JSON_EXTRACT(data, '$.event_id') = ?", [$borrow->id])
                    ->whereRaw("JSON_EXTRACT(data, '$.days_until_due') = ?", [$days])
                    ->whereRaw("JSON_EXTRACT(data, '$.session_id') = ?", [$sessionId])
                    ->exists();

                if ($alreadyNotified) {
                    continue;
                }

                $user->notify(new BorrowDueSoon($borrow, $days, $sessionId));
            }
        }
    }

    protected function sendOverdueNotifications(User $user, string $sessionId): void
    {
        $overdueBorrows = MaterialAccessEvents::with(['material.parent'])
            ->where('user_id', $user->id)
            ->where('event_type', 'borrow')
            ->where('status', 'approved')
            ->whereNull('returned_at')
            ->whereNull('completed_at')
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->get();

        foreach ($overdueBorrows as $borrow) {
            $alreadyNotified = DB::table('notifications')
                ->where('notifiable_type', get_class($user))
                ->where('notifiable_id', $user->id)
                ->whereRaw("JSON_EXTRACT(data, '$.type') = 'borrow_overdue'")
                ->whereRaw("JSON_EXTRACT(data, '$.event_id') = ?", [$borrow->id])
                ->whereRaw("JSON_EXTRACT(data, '$.session_id') = ?", [$sessionId])
                ->exists();

            if ($alreadyNotified) {
                continue;
            }

            $user->notify(new BorrowOverdue($borrow, $sessionId));
        }
    }
}
