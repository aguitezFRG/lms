<?php

namespace App\Filament\Widgets\Dashboard;

use App\Models\MaterialAccessEvents;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Notifications\Notification;

class PendingAccessesWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Pending Digital Access Requests';

    protected static ?string $pollingInterval = '15s';

    protected $listeners = ['request-actioned' => '$refresh'];

    public static function canView(): bool
    {
        return Gate::allows('viewAccess', Dashboard::class);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                MaterialAccessEvents::query()
                    ->with(['user', 'material.parent'])
                    ->where('event_type', 'request')
                    ->where('status', 'pending')
                    ->oldest()
            )
            ->columns([
                TextColumn::make('user.name')
                    ->label('Requester Name')
                    ->searchable()
                    ->weight('medium'),

                TextColumn::make('created_at')
                    ->label('Time Requested')
                    ->since()
                    ->sortable(),

                TextColumn::make('material.parent.title')
                    ->label('Material Title')
                    ->limit(45)
                    ->searchable(),

                TextColumn::make('material.parent.material_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => match ($state) {
                        1 => 'Book',
                        2 => 'Thesis',
                        3 => 'Journal',
                        4 => 'Dissertation',
                        default => 'Other',
                    })
                    ->color(fn (int $state): string => match ($state) {
                        1 => 'primary',
                        2 => 'success',
                        3 => 'warning',
                        4 => 'danger',
                        default => 'gray',
                    }),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve access request?')
                    ->modalDescription('The user will be notified and granted access to the digital material.')
                    ->modalSubmitActionLabel('Yes, approve')
                    ->action(function (MaterialAccessEvents $record): void {
                        $record->update([
                            'status'      => 'approved',
                            'approver_id' => auth()->id(),
                            'approved_at' => now(),
                            'due_at'      => now()->addDays(7)->endOfDay(),
                        ]);
                        Notification::make()->title('Access request approved')->success()->send();
                        $this->dispatch('request-actioned');
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reject access request?')
                    ->modalSubmitActionLabel('Yes, reject')
                    ->action(function (MaterialAccessEvents $record): void {
                        $record->update([
                            'status'      => 'rejected',
                            'approver_id' => auth()->id(),
                        ]);
                        Notification::make()->title('Access request rejected')->danger()->send();
                        $this->dispatch('request-actioned');
                    }),
            ])
            ->emptyStateHeading('No pending access requests')
            ->emptyStateIcon('heroicon-o-paper-airplane')
            ->paginated([10, 25]);
    }
}