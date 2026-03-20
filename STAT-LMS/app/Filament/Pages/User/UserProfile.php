<?php

namespace App\Filament\Pages\User;

use App\Enums\MaterialEventType;
use App\Enums\UserRole;
use App\Models\MaterialAccessEvents;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\Action as TableAction;
use Filament\Actions\ActionGroup;


class UserProfile extends Page implements HasTable
{
    use InteractsWithTable;

    protected string  $view  = 'filament.pages.user.user-profile';
    protected static ?string $title = 'My Profile';

    protected static bool $shouldRegisterNavigation = false;

    public string $activeTab = 'pending';

    public static function getUrl(
        array $parameters = [],
        bool $isAbsolute = true,
        ?string $panel = null,
        ?\Illuminate\Database\Eloquent\Model $tenant = null
    ): string {
        return parent::getUrl($parameters, $isAbsolute, $panel ?? 'user', $tenant);
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetTable();
    }

    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
    }

    public function markRead(string $id): void
    {
        auth()->user()
            ->notifications()
            ->where('id', $id)
            ->first()
            ?->markAsRead();
    }

    // ── Table — driven by $activeTab ────────────────────────────────────────
    public function table(Table $table): Table
    {
        $query = MaterialAccessEvents::query()
            ->with(['material.parent'])
            ->where('user_id', auth()->id())
            ->whereIn('event_type', ['request', 'borrow']);

        $query = match ($this->activeTab) {
            'pending'  => $query->where('status', 'pending'),
            'approved' => $query->where('status', 'approved'),
            'closed'   => $query->whereIn('status', ['rejected', 'cancelled', 'completed']),
            default    => $query->where('status', 'pending'),
        };

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('material.parent.title')
                    ->label('Material')
                    ->limit(40)
                    ->searchable()
                    ->wrap(),

                TextColumn::make('event_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state) => MaterialEventType::from($state)->getColor())
                    ->formatStateUsing(fn (string $state) => MaterialEventType::from($state)->getLabel()),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'   => 'warning',
                        'approved'  => 'success',
                        'rejected'  => 'danger',
                        'completed' => 'gray',
                        'cancelled' => 'gray',
                        default     => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),

                TextColumn::make('due_at')
                    ->label('Due Date')
                    ->dateTime('M d, Y')
                    ->placeholder('—')
                    ->color(fn (MaterialAccessEvents $record) => $record->is_overdue ? 'danger' : null)
                    ->description(fn (MaterialAccessEvents $record) => $record->is_overdue ? 'Overdue!' : null)
                    ->visible(fn () => in_array($this->activeTab, ['approved', 'closed'])),

                TextColumn::make('created_at')
                    ->label('Requested')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('event_type')
                    ->label('Type')
                    ->options([
                        'request' => 'Digital Request',
                        'borrow'  => 'Physical Borrow',
                    ]),
            ])
            ->actions([
                ActionGroup::make([
                    TableAction::make('cancel')
                        ->label('Cancel Request')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (MaterialAccessEvents $record) => $record->status === 'pending')
                        ->requiresConfirmation()
                        ->modalHeading('Cancel Request')
                        ->modalDescription('Are you sure you want to cancel this request? This cannot be undone.')
                        ->modalSubmitActionLabel('Yes, cancel it')
                        ->action(function (MaterialAccessEvents $record) {
                            $record->update(['status' => 'cancelled']);

                            Notification::make()
                                ->title('Request cancelled')
                                ->success()
                                ->send();
                        }),
                ])->color('gray'),
            ])
            ->emptyStateHeading(match ($this->activeTab) {
                'pending'  => 'No pending requests.',
                'approved' => 'No approved requests.',
                'closed'   => 'No closed requests.',
                default    => 'No requests found.',
            })
            ->emptyStateDescription(match ($this->activeTab) {
                'pending'  => 'Browse the catalog to submit a request.',
                'approved' => 'Approved requests will appear here.',
                'closed'   => 'Rejected or cancelled requests will appear here.',
                default    => '',
            })
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
    }

    protected function getViewData(): array
    {
        $userId = auth()->id();
        $user   = auth()->user();

        $pendingCount  = MaterialAccessEvents::where('user_id', $userId)->where('status', 'pending')->count();
        $approvedCount = MaterialAccessEvents::where('user_id', $userId)->where('status', 'approved')->count();
        $totalCount    = MaterialAccessEvents::where('user_id', $userId)
            ->whereIn('event_type', ['request', 'borrow'])
            ->count();

        $notifications = $user->notifications()->latest()->get();
        $unreadCount   = $user->unreadNotifications()->count();

        return [
            'user'          => $user,
            'roleLabel'     => UserRole::from($user->role)->getLabel(),
            'pendingCount'  => $pendingCount,
            'approvedCount' => $approvedCount,
            'totalCount'    => $totalCount,
            'notifications' => $notifications,
            'unreadCount'   => $unreadCount,
            'activeTab'     => $this->activeTab,
        ];
    }
}