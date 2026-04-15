<?php

namespace App\Filament\Resources\RrMaterialParents\Pages;

use App\Filament\Resources\RrMaterialParents\RrMaterialParentsResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRrMaterialParents extends ListRecords
{
    protected static string $resource = RrMaterialParentsResource::class;

    protected ?string $pollingInterval = '120s';

    public function getTablePollingInterval(): ?string
    {
        return '60s';
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
            CreateAction::make()
                ->color('success'),
        ];
    }
}
