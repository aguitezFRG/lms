<?php

namespace App\Filament\Resources\RrMaterials\Pages;

use App\Filament\Resources\RrMaterials\RrMaterialsResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRrMaterials extends ListRecords
{
    protected static string $resource = RrMaterialsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->color('success'),
        ];
    }
}
