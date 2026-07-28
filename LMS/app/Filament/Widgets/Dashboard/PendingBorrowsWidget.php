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
use Illuminate\Support\Facades\DB;
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
                        $result = $this->approvePendingBorrow($record);
                        Cache::forget('dashboard.pending_borrows');
                        match ($result) {
                            'approved' => Notification::make()->title('Request approved')->success()->send(),
                            'unavailable' => Notification::make()
                                ->title('Physical copy is no longer available')
                                ->body('Another visitor received this copy first. The request remains pending for review.')
                                ->warning()
                                ->send(),
                            default => Notification::make()
                                ->title('Borrow request already actioned')
                                ->body('Another visitor changed this request first. The table has been refreshed.')
                                ->warning()
                                ->send(),
                        };
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
                        $updated = $this->transitionPendingBorrow($record, [
                            'status' => 'rejected',
                            'approver_id' => auth()->id(),
                            'rejection_reason' => $data['rejection_reason'] ?? null,
                        ]);
                        Cache::forget('dashboard.pending_borrows');
                        $notification = Notification::make()
                            ->title($updated ? 'Request rejected' : 'Borrow request already actioned');

                        if ($updated) {
                            $notification->danger()->send();
                        } else {
                            $notification
                                ->body('Another visitor changed this request first. The table has been refreshed.')
                                ->warning()
                                ->send();
                        }
                        $this->dispatch('request-actioned');
                    }),
            ])
            ->emptyStateHeading('No pending borrow requests')
            ->emptyStateIcon('heroicon-o-book-open')
            ->paginated([10, 25]);
    }

    private function approvePendingBorrow(MaterialAccessEvents $record): string
    {
        return DB::transaction(function () use ($record): string {
            $pending = MaterialAccessEvents::query()
                ->whereKey($record->getKey())
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            if ($pending === null) {
                return 'stale';
            }

            $copy = $pending->material()->lockForUpdate()->first();
            if ($copy === null || ! $copy->is_available || $copy->trashed()) {
                return 'unavailable';
            }

            $pending->update([
                'status' => 'approved',
                'approver_id' => auth()->id(),
                'approved_at' => now(),
                'due_at' => now()->addDays(14)->endOfDay(),
            ]);

            return 'approved';
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function transitionPendingBorrow(MaterialAccessEvents $record, array $attributes): bool
    {
        return DB::transaction(function () use ($record, $attributes): bool {
            $pending = MaterialAccessEvents::query()
                ->whereKey($record->getKey())
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            if ($pending === null) {
                return false;
            }

            $pending->update($attributes);

            return true;
        });
    }
}
