<?php

namespace App\Filament\Resources\MaterialAccessEvents\Pages;

use App\Filament\Resources\MaterialAccessEvents\MaterialAccessEventsResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMaterialAccessEvents extends ListRecords
{
    protected static string $resource = MaterialAccessEventsResource::class;

    protected ?string $pollingInterval = '30s';

    protected $listeners = ['request-actioned' => '$refresh'];

    public function getTablePollingInterval(): ?string
    {
        return '30s';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->tooltip('Refresh the data')
                ->action(fn () => $this->dispatch('$refresh')),
        ];
    }
}
