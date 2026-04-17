<?php

namespace App\Filament\Resources\RrMaterials\Pages;

use App\Filament\Resources\RrMaterials\RrMaterialsResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditRrMaterials extends EditRecord
{
    protected static string $resource = RrMaterialsResource::class;

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return $this->record->parent->title;
    }

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
