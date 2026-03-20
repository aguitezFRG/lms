<?php

namespace App\Filament\Widgets\Dashboard;

use App\Models\MaterialAccessEvents;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Notifications\Notification;

class PendingBorrowsWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Pending Borrow Requests';

    protected static ?string $pollingInterval = '15s';

    protected $listeners = ['request-actioned' => '$refresh'];

    public static function canView(): bool
    {
        return Gate::allows('viewBorrows', Dashboard::class);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                MaterialAccessEvents::query()
                    ->with(['user', 'material.parent'])
                    ->where('event_type', 'borrow')
                    ->where('status', 'pending')
                    ->oldest()
            )
            ->columns([
                TextColumn::make('user.name')
                    ->label('Borrower Name')
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

                TextColumn::make('material.parent.author')
                    ->label('Author')
                    ->limit(30)
                    ->searchable(),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve borrow request?')
                    ->modalSubmitActionLabel('Yes, approve')
                    ->action(function (MaterialAccessEvents $record): void {
                        $record->update([
                            'status'      => 'approved',
                            'approver_id' => auth()->id(),
                            'approved_at' => now(),
                            'due_at'      => now()->addDays(14)->endOfDay(),
                        ]);
                        Notification::make()->title('Request approved')->success()->send();
                        $this->dispatch('request-actioned');
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reject borrow request?')
                    ->modalSubmitActionLabel('Yes, reject')
                    ->action(function (MaterialAccessEvents $record): void {
                        $record->update([
                            'status'      => 'rejected',
                            'approver_id' => auth()->id(),
                        ]);
                        Notification::make()->title('Request rejected')->danger()->send();
                        $this->dispatch('request-actioned');
                    }),
            ])
            ->emptyStateHeading('No pending borrow requests')
            ->emptyStateIcon('heroicon-o-book-open')
            ->paginated([10, 25]);
    }
}