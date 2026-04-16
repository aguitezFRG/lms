<?php

namespace App\Filament\Resources\RepositoryChangeLogs\Pages;

use App\Filament\Resources\RepositoryChangeLogs\RepositoryChangeLogsResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListRepositoryChangeLogs extends ListRecords
{
    protected static string $resource = RepositoryChangeLogsResource::class;

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
        ];
    }
}
