<?php

namespace App\Filament\Resources\RrMaterials\Pages;

use App\Filament\Resources\RrMaterials\RrMaterialsResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions\Action;

class CreateRrMaterials extends CreateRecord
{
    protected static string $resource = RrMaterialsResource::class;

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->color('success');
    }

    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()
            ->color('success');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->color('danger');
    }
}
