<?php

namespace App\Filament\Resources\RrMaterialParents\Pages;

use App\Filament\Resources\RrMaterialParents\RrMaterialParentsResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRrMaterialParents extends ViewRecord
{
    protected static string $resource = RrMaterialParentsResource::class;

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return $this->record->title;
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->color('danger'),
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->tooltip('Refresh the data')
                ->action(fn () => $this->dispatch('$refresh')),
        ];
    }
}
