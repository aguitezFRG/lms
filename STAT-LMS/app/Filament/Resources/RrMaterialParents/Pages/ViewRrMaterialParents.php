<?php

namespace App\Filament\Resources\RrMaterialParents\Pages;

use App\Filament\Resources\RrMaterialParents\RrMaterialParentsResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRrMaterialParents extends ViewRecord
{
    protected static string $resource = RrMaterialParentsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->color('danger'),
        ];
    }
}
