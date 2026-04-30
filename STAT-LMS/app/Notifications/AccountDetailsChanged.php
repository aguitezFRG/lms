<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AccountDetailsChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected array $changedFields
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $fields = implode(', ', array_map(
            fn ($f) => ucfirst(str_replace('_', ' ', $f)),
            $this->changedFields
        ));

        return [
            'type' => 'account_details_changed',
            'title' => 'Account Details Updated',
            'message' => "Your account details were updated by an administrator. Changed fields: {$fields}.",
        ];
    }
}
