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

class PendingAccessesWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Pending Digital Access Requests';

    protected static ?string $pollingInterval = '60s';

    protected static bool $isLazy = false;

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
                    ->label('')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->iconButton()
                    ->requiresConfirmation()
                    ->modalHeading('Approve access request?')
                    ->modalDescription('The user will be notified and granted access to the digital material.')
                    ->modalSubmitActionLabel('Yes, approve')
                    ->action(function (MaterialAccessEvents $record): void {
                        $record->update([
                            'status' => 'approved',
                            'approver_id' => auth()->id(),
                            'approved_at' => now(),
                            'due_at' => now()->addDays(7)->endOfDay(),
                        ]);
                        Cache::forget('dashboard.pending_accesses');
                        Notification::make()->title('Access request approved')->success()->send();
                        $this->dispatch('request-actioned');
                    }),

                Action::make('reject')
                    ->label('')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->iconButton()
                    ->modalHeading('Reject access request?')
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
                        Cache::forget('dashboard.pending_accesses');
                        Notification::make()->title('Access request rejected')->danger()->send();
                        $this->dispatch('request-actioned');
                    }),
            ])
            ->emptyStateHeading('No pending access requests')
            ->emptyStateIcon('heroicon-o-paper-airplane')
            ->paginated([10, 25]);
    }
}
