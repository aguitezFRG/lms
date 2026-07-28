<?php

namespace App\Listeners;

use App\Enums\UserRole;
use App\Models\MaterialAccessEvents;
use App\Models\User;
use App\Notifications\BorrowDueSoon;
use App\Notifications\BorrowOverdue;
use Illuminate\Auth\Events\Login;

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
        session()->put('borrow_reminder_login_session_id', $sessionId);

        $this->sendDueSoonNotifications($user, $sessionId);
        $this->sendOverdueNotifications($user, $sessionId);
    }

    protected function sendDueSoonNotifications(User $user, string $sessionId): void
    {
        foreach ([3, 2, 1, 0] as $days) {
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
                if ($this->alreadySentReminder($user, BorrowDueSoon::class, $borrow->id, $sessionId, $days)) {
                    continue;
                }

                $user->notifyNow(new BorrowDueSoon($borrow, $days, $sessionId));
            }
        }
    }

    protected function sendOverdueNotifications(User $user, string $sessionId): void
    {
        $overdueBorrows = MaterialAccessEvents::with(['material.parent'])
            ->where('user_id', $user->id)
            ->where('event_type', 'borrow')
            ->whereIn('status', ['approved', 'revoked'])
            ->whereNull('returned_at')
            ->whereNull('completed_at')
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->get();

        foreach ($overdueBorrows as $borrow) {
            $notification = new BorrowOverdue($borrow, $sessionId);
            $existing = $user
                ->notifications()
                ->where('type', BorrowOverdue::class)
                ->get()
                ->first(fn ($existingNotification): bool => ($existingNotification->data['event_id'] ?? null) === $borrow->id);

            if ($existing === null) {
                $user->notifyNow($notification);

                continue;
            }

            $now = now();

            $existing->forceFill([
                'data' => $notification->toDatabase($user),
                'created_at' => $now,
                'updated_at' => $now,
            ])->save();
        }
    }

    protected function alreadySentReminder(
        User $user,
        string $notificationClass,
        string $eventId,
        string $sessionId,
        ?int $daysUntilDue = null
    ): bool {
        return $user
            ->notifications()
            ->where('type', $notificationClass)
            ->where('created_at', '>=', now()->subDays(2))
            ->get()
            ->contains(function ($notification) use ($eventId, $sessionId, $daysUntilDue): bool {
                if (($notification->data['event_id'] ?? null) !== $eventId) {
                    return false;
                }

                if (($notification->data['session_id'] ?? null) !== $sessionId) {
                    return false;
                }

                return $daysUntilDue === null
                    || (int) ($notification->data['days_until_due'] ?? 0) === $daysUntilDue;
            });
    }
}
