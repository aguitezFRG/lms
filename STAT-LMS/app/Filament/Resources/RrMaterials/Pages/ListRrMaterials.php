<?php

namespace App\Filament\Resources\RrMaterials\Pages;

use App\Filament\Resources\RrMaterials\RrMaterialsResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRrMaterials extends ListRecords
{
    protected static string $resource = RrMaterialsResource::class;

    protected ?string $pollingInterval = '60s';

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
