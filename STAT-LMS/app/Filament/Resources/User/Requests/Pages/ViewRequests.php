<?php

namespace App\Filament\Resources\User\Requests\Pages;

use App\Filament\Resources\User\Requests\RequestsResource;
use App\Models\MaterialAccessEvents;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewRequests extends ViewRecord
{
    protected static string $resource = RequestsResource::class;

    protected ?string $pollingInterval = '60s';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cancel')
                ->label('Cancel Request')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->status === 'pending')
                ->requiresConfirmation()
                ->modalHeading('Cancel this request?')
                ->modalDescription('This will permanently cancel your request. This action cannot be undone.')
                ->modalSubmitActionLabel('Yes, cancel it')
                ->action(function () {
                    $this->record->update(['status' => 'cancelled']);

                    Notification::make()
                        ->title('Request cancelled')
                        ->success()
                        ->send();

                    $this->redirect(RequestsResource::getUrl('index'));
                }),

            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->tooltip('Refresh the data')
                ->action(fn () => $this->dispatch('$refresh')),
        ];
    }
}