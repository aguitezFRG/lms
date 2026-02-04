<?php

namespace App\Filament\Resources\RepositoryChangeLogs\Pages;

use App\Filament\Resources\RepositoryChangeLogs\RepositoryChangeLogsResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRepositoryChangeLogs extends ViewRecord
{
    protected static string $resource = RepositoryChangeLogsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
