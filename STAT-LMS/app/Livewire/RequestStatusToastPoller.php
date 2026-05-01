<?php

namespace App\Livewire;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;
use Livewire\Component;

class RequestStatusToastPoller extends Component
{
    protected const MAX_VISIBLE_TOASTS = 3;
    protected const DISPLAYED_REQUEST_STATUS_TOAST_IDS_KEY = 'request_status_toast_displayed_ids';

    public ?string $lastSeenCreatedAt = null;

    public array $lastSeenIdsAtTimestamp = [];

    public bool $sessionRemindersQueued = false;

    public function mount(): void
    {
        $latest = auth()->user()
            ->notifications()
            ->where('created_at', '>=', now()->subDays(2))
            ->where('data->type', 'request_status_changed')
            ->orderByDesc('created_at')
            ->first();

        if (! $latest) {
            return;
        }

        $this->lastSeenCreatedAt = $latest->created_at->toDateTimeString();
        $this->lastSeenIdsAtTimestamp = auth()->user()
            ->notifications()
            ->where('created_at', $latest->created_at)
            ->where('data->type', 'request_status_changed')
            ->pluck('id')
            ->values()
            ->all();
    }

    public function pollForNewNotifications(): void
    {
        $toastItems = collect();

        if (! $this->sessionRemindersQueued) {
            $toastItems = $toastItems->merge($this->sessionReminderNotifications());
            $this->sessionRemindersQueued = true;
        }

        $newRequestStatusNotifications = $this->requestStatusNotifications();
        $alreadyDisplayedIds = $this->displayedRequestStatusToastIds();
        $newRequestStatusNotifications = $newRequestStatusNotifications
            ->reject(fn (DatabaseNotification $notification): bool => in_array($notification->id, $alreadyDisplayedIds, true))
            ->values();

        $toastItems = $toastItems->merge($newRequestStatusNotifications->map(
            fn (DatabaseNotification $notification): array => [
                'id' => $notification->id,
                'created_at' => $notification->created_at,
                'title' => $notification->data['title'] ?? 'Request updated',
                'message' => $notification->data['message'] ?? '',
                'status' => $this->resolveToastStatus($notification) ?? 'info',
                'persistent' => false,
                'kind' => 'normal',
            ]
        ));

        $toastItems = $toastItems
            ->filter(fn (array $toast): bool => filled($toast['status']))
            ->sortByDesc(fn (array $toast): string => $toast['created_at']->toDateTimeString().'|'.$toast['id'])
            ->values();

        $toDisplay = $toastItems->take(self::MAX_VISIBLE_TOASTS);

        foreach ($toDisplay as $toast) {
            $this->dispatch(
                'request-status-toast',
                toastId: $toast['id'],
                title: $toast['title'],
                message: $toast['message'],
                status: $toast['status'],
                persistent: $toast['persistent'],
                kind: $toast['kind'],
            );
        }

        $overflowCount = $toastItems->count() - $toDisplay->count();

        if ($overflowCount > 0) {
            $this->dispatch(
                'request-status-toast',
                toastId: null,
                title: 'More notifications',
                message: "You got {$overflowCount} more notifications",
                status: 'info',
                persistent: false,
                kind: 'summary',
            );
        }

        if ($newRequestStatusNotifications->isNotEmpty()) {
            $this->dispatch('request-status-notifications-updated')
                ->to(NotificationBell::class);

            $this->storeCursor($newRequestStatusNotifications);
            $this->rememberDisplayedRequestStatusToastIds(
                $newRequestStatusNotifications->pluck('id')->all()
            );
        }
    }

    public function render(): View
    {
        return view('livewire.request-status-toast-poller');
    }

    /**
     * @return Collection<int, DatabaseNotification>
     */
    protected function requestStatusNotifications(): Collection
    {
        $query = auth()->user()
            ->notifications()
            ->where('created_at', '>=', now()->subDays(2))
            ->where('data->type', 'request_status_changed')
            ->orderBy('created_at')
            ->orderBy('id');

        if ($this->lastSeenCreatedAt) {
            $createdAt = $this->lastSeenCreatedAt;
            $ids = $this->lastSeenIdsAtTimestamp;

            $query->where(function (Builder $q) use ($createdAt, $ids): void {
                $q->where('created_at', '>', $createdAt)
                    ->orWhere(function (Builder $sub) use ($createdAt, $ids): void {
                        $sub->where('created_at', '=', $createdAt);

                        if ($ids !== []) {
                            $sub->whereNotIn('id', $ids);
                        }
                    });
            });
        }

        return $query
            ->get()
            ->values();
    }

    /**
     * @return Collection<int, array{id:string,created_at:\Illuminate\Support\Carbon,title:string,message:string,status:string,persistent:bool,kind:string}>
     */
    protected function sessionReminderNotifications(): Collection
    {
        $sessionId = session()->getId();

        return auth()->user()
            ->notifications()
            ->where('created_at', '>=', now()->subDays(2))
            ->where(function (Builder $query) use ($sessionId): void {
                $query
                    ->where(function (Builder $sub) use ($sessionId): void {
                        $sub->where('data->type', 'borrow_due_soon')
                            ->where('data->days_until_due', 1)
                            ->where('data->session_id', $sessionId);
                    })
                    ->orWhere(function (Builder $sub) use ($sessionId): void {
                        $sub->where('data->type', 'borrow_overdue')
                            ->where('data->session_id', $sessionId);
                    });
            })
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(function (DatabaseNotification $notification): array {
                $isOverdue = ($notification->data['type'] ?? '') === 'borrow_overdue';

                return [
                    'id' => $notification->id,
                    'created_at' => $notification->created_at,
                    'title' => $notification->data['title'] ?? 'Reminder',
                    'message' => $notification->data['message'] ?? '',
                    'status' => $isOverdue ? 'danger' : 'warning',
                    'persistent' => $isOverdue,
                    'kind' => 'normal',
                ];
            })
            ->values();
    }

    protected function resolveToastStatus(DatabaseNotification $notification): ?string
    {
        $content = strtolower(trim(
            ($notification->data['title'] ?? '').' '.($notification->data['message'] ?? '')
        ));

        return match (true) {
            str_contains($content, 'approved') => 'success',
            str_contains($content, 'rejected') => 'danger',
            str_contains($content, 'revoked') => 'danger',
            default => null,
        };
    }

    protected function storeCursor(Collection $notifications): void
    {
        /** @var DatabaseNotification|null $latest */
        $latest = $notifications->last();

        if (! $latest) {
            return;
        }

        $latestCreatedAt = $latest->created_at->toDateTimeString();
        $idsAtLatestTimestamp = $notifications
            ->filter(fn (DatabaseNotification $notification): bool => $notification->created_at->toDateTimeString() === $latestCreatedAt)
            ->pluck('id')
            ->values()
            ->all();

        $this->lastSeenCreatedAt = $latestCreatedAt;
        $this->lastSeenIdsAtTimestamp = $idsAtLatestTimestamp;
    }

    /**
     * @return array<int, string>
     */
    protected function displayedRequestStatusToastIds(): array
    {
        $ids = session()->get(self::DISPLAYED_REQUEST_STATUS_TOAST_IDS_KEY, []);

        return is_array($ids) ? array_values(array_filter($ids, 'is_string')) : [];
    }

    /**
     * @param array<int, string> $ids
     */
    protected function rememberDisplayedRequestStatusToastIds(array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $merged = array_values(array_unique([
            ...$this->displayedRequestStatusToastIds(),
            ...$ids,
        ]));

        // Keep the session footprint bounded.
        session()->put(
            self::DISPLAYED_REQUEST_STATUS_TOAST_IDS_KEY,
            array_slice($merged, -500)
        );
    }
}
