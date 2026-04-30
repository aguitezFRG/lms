<?php

namespace App\Livewire;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;
use Livewire\Component;

class RequestStatusToastPoller extends Component
{
    public ?string $lastSeenCreatedAt = null;

    public array $lastSeenIdsAtTimestamp = [];

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
        $hasNewNotifications = false;
        $newNotifications = $this->requestStatusNotifications();

        foreach ($newNotifications as $notification) {
            $hasNewNotifications = true;
            $toastStatus = $this->resolveToastStatus($notification);

            if (! $toastStatus) {
                continue;
            }

            $this->dispatch(
                'request-status-toast',
                title: $notification->data['title'] ?? 'Request updated',
                message: $notification->data['message'] ?? '',
                status: $toastStatus,
            );
        }

        if ($hasNewNotifications) {
            $this->dispatch('request-status-notifications-updated')
                ->to(NotificationBell::class);

            $this->storeCursor($newNotifications);
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

            $query->where(function ($q) use ($createdAt, $ids): void {
                $q->where('created_at', '>', $createdAt)
                    ->orWhere(function ($sub) use ($createdAt, $ids): void {
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
}
