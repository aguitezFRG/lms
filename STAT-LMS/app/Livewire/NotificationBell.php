<?php

namespace App\Livewire;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class NotificationBell extends Component
{
    public bool $open = false;

    public array $seenRequestStatusNotificationIds = [];

    public function mount(): void
    {
        $this->seenRequestStatusNotificationIds = $this->requestStatusNotifications()
            ->pluck('id')
            ->all();
    }

    public function close(): void
    {
        $this->open = false;
    }

    #[On('request-status-notifications-updated')]
    public function refreshNotifications(): void {}

    public function markRead(string $id): void
    {
        auth()->user()
            ->notifications()
            ->where('id', $id)
            ->first()
            ?->markAsRead();
    }

    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
    }

    public function pollForNewNotifications(): void
    {
        $newNotifications = $this->requestStatusNotifications()
            ->reject(fn (DatabaseNotification $notification): bool => in_array($notification->id, $this->seenRequestStatusNotificationIds, true))
            ->values();

        foreach ($newNotifications as $notification) {
            $toastStatus = $this->resolveToastStatus($notification);

            if (! $toastStatus) {
                $this->seenRequestStatusNotificationIds[] = $notification->id;

                continue;
            }

            $this->dispatch(
                'request-status-toast',
                title: $notification->data['title'] ?? 'Request updated',
                message: $notification->data['message'] ?? '',
                status: $toastStatus,
            );

            $this->seenRequestStatusNotificationIds[] = $notification->id;
        }
    }

    #[Computed]
    public function notifications(): array
    {
        return auth()->user()
            ->notifications()
            ->where('created_at', '>=', now()->subDays(2))
            ->latest()
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'title' => $n->data['title'] ?? 'Notification',
                'message' => $n->data['message'] ?? '',
                'type' => $n->data['type'] ?? 'general',
                'since' => $n->created_at->diffForHumans(),
                'is_unread' => is_null($n->read_at),
            ])
            ->values()
            ->toArray();
    }

    #[Computed]
    public function unreadCount(): int
    {
        return auth()->user()->unreadNotifications()->count();
    }

    public function render(): View
    {
        return view('livewire.notification-bell');
    }

    /**
     * @return Collection<int, DatabaseNotification>
     */
    protected function requestStatusNotifications(): Collection
    {
        return auth()->user()
            ->notifications()
            ->where('created_at', '>=', now()->subDays(2))
            ->where('data->type', 'request_status_changed')
            ->latest('created_at')
            ->get()
            ->sortBy('created_at')
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
}
