<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RepositoryTable: string implements HasLabel
{
    case RR_MATERIAL_PARENTS = 'rr_material_parents';
    case RR_MATERIALS = 'rr_materials';
    case MATERIAL_ACCESS_EVENTS = 'material_access_events';
    case USERS = 'users';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::RR_MATERIAL_PARENTS => 'RR Material Parents',
            self::RR_MATERIALS => 'RR Materials',
            self::MATERIAL_ACCESS_EVENTS => 'Material Access Events',
            self::USERS => 'Users',
        };
    }
}
