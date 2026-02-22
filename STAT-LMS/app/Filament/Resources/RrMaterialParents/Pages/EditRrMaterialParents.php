<?php

namespace App\Filament\Resources\RrMaterialParents\Pages;

use App\Filament\Resources\RrMaterialParents\RrMaterialParentsResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\Action;

class EditRrMaterialParents extends EditRecord
{
    protected static string $resource = RrMaterialParentsResource::class;

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
