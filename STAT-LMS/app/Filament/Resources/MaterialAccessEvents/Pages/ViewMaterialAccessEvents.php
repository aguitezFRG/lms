<?php

namespace App\Filament\Resources\MaterialAccessEvents\Pages;

use App\Filament\Resources\MaterialAccessEvents\MaterialAccessEventsResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMaterialAccessEvents extends ViewRecord
{
    protected static string $resource = MaterialAccessEventsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn () => in_array($this->record->status, ['pending', 'rejected', 'approved'])),
        ];
    }
}
