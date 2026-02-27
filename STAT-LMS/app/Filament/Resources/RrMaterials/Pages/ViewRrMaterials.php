<?php

namespace App\Filament\Resources\RrMaterials\Pages;

use App\Filament\Resources\RrMaterials\RrMaterialsResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\Action;

use App\Enums\UserRole;

class ViewRrMaterials extends ViewRecord
{
    protected static string $resource = RrMaterialsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewDocument')
                ->label('View Document')
                ->color('success') // UP Forest Green
                ->visible(fn ($record) => $this->canViewDocument($record))
                ->url(fn ($record) => route('materials.stream', ['record' => $record->id]), shouldOpenInNewTab: true),

            EditAction::make()
                ->color('danger'),
        ];
    }

    protected function canViewDocument($record): bool
    {
        // 1. Must be a digital copy
        if (!$record->is_digital || empty($record->file_name)) {
            return false;
        }

        $user = auth()->user();
        if (!$user) {
            return false; // Not logged in, deny access
        }

        $user_access_level = UserRole::from($user->role)->getAccessLevel();
        $accessLevel = (int) $record->parent->access_level;

        // 2. User's access level must be greater than or equal to the material's access level
        return $user_access_level >= $accessLevel;
    }
}
