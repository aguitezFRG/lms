<?php

namespace App\Filament\Resources\MaterialAccessEvents\Pages;

use App\Filament\Resources\MaterialAccessEvents\MaterialAccessEventsResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\Action;

use Illuminate\Support\Facades\Log;

class EditMaterialAccessEvents extends EditRecord
{
    protected static string $resource = MaterialAccessEventsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ViewAction::make(),
            // DeleteAction::make(),
            // ForceDeleteAction::make(),
            // RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['approver_id'] = auth()->id();

        if (!empty($data['due_at'])) {
            $data['due_at'] = \Carbon\Carbon::parse($data['due_at'])->endOfDay()->toDateTimeString();
        }

        return $data;
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
