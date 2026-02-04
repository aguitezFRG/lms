<?php

namespace App\Filament\Resources\RepositoryChangeLogs\Pages;

use App\Filament\Resources\RepositoryChangeLogs\RepositoryChangeLogsResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRepositoryChangeLogs extends ListRecords
{
    protected static string $resource = RepositoryChangeLogsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
