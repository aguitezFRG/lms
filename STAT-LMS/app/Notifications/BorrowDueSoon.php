<?php

namespace App\Notifications;

use App\Models\MaterialAccessEvents;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class BorrowDueSoon extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected MaterialAccessEvents $event,
        protected int $daysUntilDue,
        protected ?string $sessionId = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $title = $this->event->material?->parent?->title ?? 'a material';
        $due = $this->event->due_at?->format('F d, Y') ?? 'soon';

        return [
            'type' => 'borrow_due_soon',
            'title' => match ($this->daysUntilDue) {
                0 => 'Borrow Due Today!',
                1 => 'Borrow Due Tomorrow!',
                default => "Borrow Due in {$this->daysUntilDue} Days",
            },
            'message' => "Your borrowed copy of \"{$title}\" is due on {$due}. Please return it on time to avoid overdue penalties.",
            'event_id' => $this->event->id,
            'days_until_due' => $this->daysUntilDue,
            'session_id' => $this->sessionId,
        ];
    }
}
