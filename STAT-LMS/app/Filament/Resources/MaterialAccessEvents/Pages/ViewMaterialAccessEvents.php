<?php

namespace App\Filament\Resources\MaterialAccessEvents\Pages;

use App\Filament\Resources\MaterialAccessEvents\MaterialAccessEventsResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMaterialAccessEvents extends ViewRecord
{
    protected static string $resource = MaterialAccessEventsResource::class;

    protected ?string $pollingInterval = '60s';

    protected $listeners = ['request-actioned' => '$refresh'];

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn () => in_array($this->record->status, ['pending', 'rejected', 'approved'])),
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->tooltip('Refresh the data')
                ->action(fn () => $this->dispatch('$refresh')),
        ];
    }
}
