<?php

namespace App\Filament\Resources\MaterialAccessEvents\Pages;

use App\Filament\Resources\MaterialAccessEvents\MaterialAccessEventsResource;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditMaterialAccessEvents extends EditRecord
{
    protected static string $resource = MaterialAccessEventsResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if ($this->record->trashed()) {
            abort(403, 'Editing a deleted/revoked record is not permitted.');
        }
    }

    public function getHeading(): string|Htmlable
    {
        return $this->record->material->parent->title;
    }

    protected function getHeaderActions(): array
    {
        return [
            // ViewAction::make(),
            // DeleteAction::make(),
            // ForceDeleteAction::make(),
            // RestoreAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $this->dispatch('request-actioned');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['approver_id'] = auth()->id();

        if ($data['status'] !== 'approved') {
            $data['due_at'] = null;
        }

        if (! empty($data['due_at'])) {
            $data['due_at'] = Carbon::parse($data['due_at'])->endOfDay()->toDateTimeString();
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
