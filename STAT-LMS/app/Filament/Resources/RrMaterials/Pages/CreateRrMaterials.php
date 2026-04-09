<?php

namespace App\Filament\Resources\RrMaterials\Pages;

use App\Filament\Resources\RrMaterials\RrMaterialsResource;
use App\Models\RrMaterials;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;

class CreateRrMaterials extends CreateRecord
{
    protected static string $resource = RrMaterialsResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $isDigital = $data['is_digital'] ?? true;
        $copies = $isDigital ? 1 : max(1, (int) ($data['number_of_copies'] ?? 1));

        // Remove virtual field before persisting
        unset($data['number_of_copies']);

        $first = null;
        for ($i = 0; $i < $copies; $i++) {
            $record = static::getModel()::create($data);
            if ($first === null) {
                $first = $record;
            }
        }

        return $first;
    }

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
