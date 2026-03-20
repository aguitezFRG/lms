<?php

namespace App\Notifications;

use App\Models\RrMaterialParents;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AccessLevelChanged extends Notification
{
    use Queueable;

    public function __construct(
        protected RrMaterialParents $material,
        protected int $oldLevel,
        protected int $newLevel
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $levelLabel = fn (int $l) => match ($l) {
            1 => 'Public', 2 => 'Restricted', 3 => 'Confidential', default => 'Unknown'
        };

        return [
            'type'        => 'access_level_changed',
            'title'       => 'Material Access Level Changed',
            'message'     => "The access level for \"{$this->material->title}\" changed from {$levelLabel($this->oldLevel)} to {$levelLabel($this->newLevel)}. This may affect your ability to access this material.",
            'material_id' => $this->material->id,
        ];
    }
}