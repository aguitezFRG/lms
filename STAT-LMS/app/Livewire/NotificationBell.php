<?php

namespace App\Livewire;

use Livewire\Attributes\Computed;
use Livewire\Component;

class NotificationBell extends Component
{
    public bool $open = false;

    public function close(): void
    {
        $this->open = false;
    }

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

    public function render(): \Illuminate\View\View
    {
        return view('livewire.notification-bell');
    }
}
