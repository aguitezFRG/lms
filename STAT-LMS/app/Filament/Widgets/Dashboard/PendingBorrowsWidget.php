<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Pages\Dashboard;
use App\Models\MaterialAccessEvents;
use Filament\Actions\Action;
use Filament\Forms\Components\TagsInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

class PendingBorrowsWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Pending Borrow Requests';

    protected static ?string $pollingInterval = '60s';

    protected static bool $isLazy = true;

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
                    ->label('')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->iconButton()
                    ->requiresConfirmation()
                    ->modalHeading('Approve borrow request?')
                    ->modalSubmitActionLabel('Yes, approve')
                    ->action(function (MaterialAccessEvents $record): void {
                        $record->update([
                            'status' => 'approved',
                            'approver_id' => auth()->id(),
                            'approved_at' => now(),
                            'due_at' => now()->addDays(14)->endOfDay(),
                        ]);
                        Cache::forget('dashboard.pending_borrows');
                        Notification::make()->title('Request approved')->success()->send();
                        $this->dispatch('request-actioned');
                    }),

                Action::make('reject')
                    ->label('')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->iconButton()
                    ->modalHeading('Reject borrow request?')
                    ->modalSubmitActionLabel('Yes, reject')
                    ->form([
                        TagsInput::make('rejection_reason')
                            ->label('Rejection Reason(s)')
                            ->placeholder('Select or type a reason...')
                            ->suggestions([
                                'Overdue materials on record',
                                'Outstanding fees',
                                'Request limit reached',
                                'Incomplete request details',
                                'Access level restriction',
                                'Material currently unavailable',
                                'Policy violation',
                                'Duplicate request',
                            ])
                            ->hint('Select from suggestions or type a custom reason and press Enter.')
                            ->hintColor('gray'),
                    ])
                    ->action(function (array $data, MaterialAccessEvents $record): void {
                        $record->update([
                            'status' => 'rejected',
                            'approver_id' => auth()->id(),
                            'rejection_reason' => $data['rejection_reason'] ?? null,
                        ]);
                        Cache::forget('dashboard.pending_borrows');
                        Notification::make()->title('Request rejected')->danger()->send();
                        $this->dispatch('request-actioned');
                    }),
            ])
            ->emptyStateHeading('No pending borrow requests')
            ->emptyStateIcon('heroicon-o-book-open')
            ->paginated([10, 25]);
    }
}
