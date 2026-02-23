<?php

namespace App\Filament\Resources\RrMaterials\Pages;

use App\Filament\Resources\RrMaterials\RrMaterialsResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\Action;

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
        $accessLevel = (int) $record->parent->access_level;

        return match ($accessLevel) {
            1 => in_array($user->role, config('api.level_1_access_roles')),

            2 => in_array($user->role, config('api.level_2_access_roles')),

            3 => in_array($user->role, config('api.level_3_access_roles')),

            default => false,
        };
    }
}
