<?php

namespace App\Notifications;

use App\Models\MaterialAccessEvents;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RequestStatusChanged extends Notification
{
    use Queueable;

    public function __construct(
        protected MaterialAccessEvents $event
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $title   = $this->event->material?->parent?->title ?? 'a material';
        $status  = strtolower(ucfirst($this->event->status));
        $isDigital = $this->event->material?->is_digital;
        $type    = $isDigital ? 'Digital Request' : 'Borrow Request';

        $message = "Your {$type} for \"{$title}\" has been {$status}.";

        if ($this->event->status === 'rejected' && filled($this->event->rejection_reason)) {
            $reasons = is_array($this->event->rejection_reason)
                ? implode(', ', $this->event->rejection_reason)
                : $this->event->rejection_reason;
            $message .= " Reason: {$reasons}";
        }

        return [
            'type'    => 'request_status_changed',
            'title'   => "Your {$type} was {$status}",
            'message' => $message,
            'event_id' => $this->event->id,
        ];
    }
}