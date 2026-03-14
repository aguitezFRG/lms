<?php

namespace App\Filament\Resources\MaterialAccessEvents\Pages;

use App\Filament\Resources\MaterialAccessEvents\MaterialAccessEventsResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMaterialAccessEvents extends CreateRecord
{
    protected static string $resource = MaterialAccessEventsResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['approver_id'] = auth()->id();

        if (!empty($data['due_at'])) {
            $data['due_at'] = $data['due_at'] . ' 23:59:59';
        }

        return $data;
    }
}
