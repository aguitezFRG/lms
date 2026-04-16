<?php

namespace App\Filament\Resources\RrMaterials\Pages;

use App\Filament\Resources\RrMaterials\RrMaterialsResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\Action;

class EditRrMaterials extends EditRecord
{
    protected static string $resource = RrMaterialsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('View')
                ->color('success'),

            DeleteAction::make()
                ->label('Remove')
                ->modalHeading('Confirm Removal') // Changes the text inside the pop-up
                ->color('danger'), // Keeps it red for safety
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->color('success');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->color('danger');
    }
}
