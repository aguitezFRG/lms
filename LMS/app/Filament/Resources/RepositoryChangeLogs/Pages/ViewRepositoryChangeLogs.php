<?php

namespace App\Filament\Resources\RepositoryChangeLogs\Pages;

use App\Filament\Resources\RepositoryChangeLogs\RepositoryChangeLogsResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewRepositoryChangeLogs extends ViewRecord
{
    protected static string $resource = RepositoryChangeLogsResource::class;

    public function getHeading(): string|Htmlable
    {
        return ucfirst($this->record->change_type).': '.$this->record->table_changed;
    }

    protected function getHeaderActions(): array
    {
        return [
            // EditAction::make(),
        ];
    }
}
