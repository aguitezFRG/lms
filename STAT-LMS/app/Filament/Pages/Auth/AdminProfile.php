<?php

namespace App\Filament\Pages\Auth;

use App\Enums\MaterialEventType;
use App\Enums\UserRole;
use App\Models\MaterialAccessEvents;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class AdminProfile extends Page implements HasTable
{
    use InteractsWithTable;

    protected string  $view  = 'filament.pages.auth.admin-profile';
    protected static ?string $title = 'My Profile';

    protected static bool $shouldRegisterNavigation = false;

    public string $activeTab = 'history';

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

    // ── Profile infolist ────────────────────────────────────────────────────
    public function getProfileInfolist(): array
    {
        $user = auth()->user();

        return [
            Section::make()
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('name')
                            ->label('Full Name')
                            ->state(trim(implode(' ', array_filter([
                                $user->f_name,
                                $user->m_name ? mb_substr($user->m_name, 0, 1) . '.' : null,
                                $user->l_name,
                            ]))) ?: $user->name),

                        TextEntry::make('role')
                            ->label('Role')
                            ->badge()
                            ->state($user->role)
                            ->color(fn () => UserRole::from($user->role)->getColor())
                            ->formatStateUsing(fn () => UserRole::from($user->role)->getLabel()),

                        TextEntry::make('email')
                            ->label('Email')
                            ->state($user->email)
                            ->icon('heroicon-m-envelope'),

                        TextEntry::make('std_number')
                            ->label('Student Number')
                            ->state($user->std_number ?? '—'),

                        TextEntry::make('id')
                            ->label('UUID')
                            ->state($user->id)
                            ->copyable()
                            ->columnSpanFull(),
                    ]),
                ])
                ->columnSpanFull(),
        ];
    }

    // ── Notifications infolist ──────────────────────────────────────────────
    public function getNotificationsInfolist(): array
    {
        $notifications = auth()->user()->notifications()->latest()->get();

        return [
            Section::make('Notifications')
                ->schema([
                    RepeatableEntry::make('notifications')
                        ->label('')
                        ->schema([
                            TextEntry::make('data.title')
                                ->label('')
                                ->weight('bold')
                                ->size('sm'),

                            TextEntry::make('data.message')
                                ->label('')
                                ->size('sm')
                                ->color('gray'),

                            TextEntry::make('created_at')
                                ->label('')
                                ->since()
                                ->color('gray')
                                ->size('xs'),
                        ])
                        ->columns(1)
                        ->state($notifications),
                ])
                ->columnSpanFull(),
        ];
    }

    // ── Table (history) ─────────────────────────────────────────────────────
    public function table(Table $table): Table
    {
        return $table
            ->query(
                MaterialAccessEvents::query()
                    ->with(['material.parent'])
                    ->where('user_id', auth()->id())
                    ->whereIn('event_type', ['borrow', 'request'])
            )
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
                    ->description(fn (MaterialAccessEvents $record) => $record->is_overdue ? 'Overdue!' : null),

                TextColumn::make('created_at')
                    ->label('Requested')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending'   => 'Pending',
                        'approved'  => 'Approved',
                        'rejected'  => 'Rejected',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
                SelectFilter::make('event_type')
                    ->label('Type')
                    ->options([
                        'request' => 'Digital Request',
                        'borrow'  => 'Physical Borrow',
                    ]),
            ])
            ->emptyStateHeading('No access history yet.')
            ->emptyStateIcon('heroicon-o-clock');
    }

    protected function getViewData(): array
    {
        $user          = auth()->user();
        $notifications = $user->notifications()->latest()->get();
        $unreadCount   = $user->unreadNotifications()->count();

        return [
            'user'          => $user,
            'unreadCount'   => $unreadCount,
            'notifications' => $notifications,
            'activeTab'     => $this->activeTab,
            'profileSchema' => $this->getProfileInfolist(),
        ];
    }
}