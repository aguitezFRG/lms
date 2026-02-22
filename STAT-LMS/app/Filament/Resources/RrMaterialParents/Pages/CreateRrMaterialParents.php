<?php

namespace App\Filament\Resources\RrMaterialParents\Pages;

use App\Filament\Resources\RrMaterialParents\RrMaterialParentsResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions\Action;

class CreateRrMaterialParents extends CreateRecord
{
    protected static string $resource = RrMaterialParentsResource::class;

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
