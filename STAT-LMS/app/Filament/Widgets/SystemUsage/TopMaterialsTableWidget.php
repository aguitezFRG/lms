<?php

namespace App\Filament\Widgets\SystemUsage;

use App\Filament\Pages\SystemUsage;
use App\Models\MaterialAccessEvents;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Gate;

class TopMaterialsTableWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Top 5 Most Requested Materials';

    protected static ?string $pollingInterval = '120s';

    protected static bool $isLazy = true;

    public static function canView(): bool
    {
        return Gate::allows('viewAny', SystemUsage::class);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                MaterialAccessEvents::query()
                    ->selectRaw('rr_material_id as id, rr_material_id, COUNT(*) as request_count')
                    ->whereIn('event_type', ['request', 'borrow'])
                    ->groupBy('rr_material_id')
                    ->orderByDesc('request_count')
                    ->limit(5)
                    ->with('material.parent')
            )
            ->columns([
                TextColumn::make('rank')
                    ->label('Rank')
                    ->state(fn ($record, $rowLoop) => $rowLoop->iteration)
                    ->width('60px')
                    ->alignment('center'),

                TextColumn::make('material.parent.title')
                    ->label('Material Title')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->weight('medium'),

                TextColumn::make('material.parent.author')
                    ->label('Author')
                    ->sortable()
                    ->limit(30)
                    ->toggleable(),

                TextColumn::make('request_count')
                    ->label('Request Count')
                    ->sortable()
                    ->alignment('center')
                    ->width('120px')
                    ->badge()
                    ->color('primary'),
            ])
            ->emptyStateHeading('No material requests yet')
            ->emptyStateDescription('Once users start requesting materials, they will appear here.')
            ->emptyStateIcon('heroicon-o-document-text')
            ->paginated(false)
            ->deferLoading();
    }
}
