<?php

namespace App\Support;

use Filament\Notifications\Notification;
use Livewire\Livewire;

class DemoNotification extends Notification
{
    public function send(): static
    {
        if (! config('demo.enabled')) {
            return parent::send();
        }

        if (! Livewire::isLivewireRequest() || ! ($component = Livewire::current())) {
            return $this;
        }

        $serialized = $this->toArray();

        $component->dispatch('demo-notification', notification: [
            'title' => $serialized['title'],
            'body' => $serialized['body'],
            'status' => $serialized['status'],
            'duration' => $serialized['duration'],
            'icon' => $serialized['icon'],
            'identifier' => $serialized['id'],
        ]);

        return $this;
    }
}
