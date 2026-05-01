<?php

namespace App\Notifications;

use App\Models\MaterialAccessEvents;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class BorrowOverdue extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected MaterialAccessEvents $event,
        protected ?string $sessionId = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $title = $this->event->material?->parent?->title ?? 'a material';
        $due = $this->event->due_at?->format('F d, Y') ?? 'an earlier date';

        return [
            'type' => 'borrow_overdue',
            'title' => 'Borrow Overdue',
            'message' => "Your borrowed copy of \"{$title}\" was due on {$due}. Please return it as soon as possible.",
            'event_id' => $this->event->id,
            'session_id' => $this->sessionId,
        ];
    }
}
