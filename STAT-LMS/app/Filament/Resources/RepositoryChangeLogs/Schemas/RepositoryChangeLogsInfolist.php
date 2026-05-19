<?php

namespace App\Filament\Resources\RepositoryChangeLogs\Schemas;

use App\Enums\RepositoryChangeType;
use App\Models\RepositoryChangeLogs;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RepositoryChangeLogsInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Change Overview')
                    ->columnSpanFull()
                    ->components([
                        Grid::make(3)
                            ->components([
                                TextEntry::make('editor_id')
                                    ->label('Editor')
                                    ->tooltip(fn (RepositoryChangeLogs $record) => $record->editor?->name),

                                TextEntry::make('table_changed')
                                    ->label('Table Changed')
                                    ->badge(),

                                TextEntry::make('change_type')
                                    ->label('Change Type')
                                    ->badge()
                                    ->color(fn (string $state) => RepositoryChangeType::from($state)->getColor()),
                            ]),

                        Grid::make(2)
                            ->components([
                                TextEntry::make('rr_material_id')
                                    ->label('Related Material Copy')
                                    ->placeholder('N/A')
                                    ->visible(fn (RepositoryChangeLogs $record) => $record->rr_material_id !== null)
                                    ->tooltip(fn (RepositoryChangeLogs $record) => $record->material?->parent?->title),

                                TextEntry::make('material_parent_id')
                                    ->label('Related Material Parent')
                                    ->placeholder('N/A')
                                    ->visible(fn (RepositoryChangeLogs $record) => $record->material_parent_id !== null)
                                    ->tooltip(fn (RepositoryChangeLogs $record) => $record->materialParent?->title),

                                TextEntry::make('target_user_id')
                                    ->label('Target User')
                                    ->placeholder('N/A')
                                    ->visible(fn (RepositoryChangeLogs $record) => $record->target_user_id !== null)
                                    ->tooltip(fn (RepositoryChangeLogs $record) => $record->targetUser?->name),

                                TextEntry::make('changed_at')
                                    ->label('Changed At')
                                    ->datetime('F d, Y h:i A'),
                            ]),
                    ]),

                Section::make('Change Details')
                    ->columnSpanFull()
                    ->components([
                        RepeatableEntry::make('change_rows')
                            ->label('Changes Made')
                            ->placeholder('No changes recorded.')
                            ->extraAttributes(['class' => 'rr-change-details-table'])
                            ->table([
                                TableColumn::make('Field')->width('25%'),
                                TableColumn::make('Old Value')->width('37.5%'),
                                TableColumn::make('New Value')->width('37.5%'),
                            ])
                            ->schema([
                                TextEntry::make('field')
                                    ->label('Field')
                                    ->fontFamily('mono'),

                                TextEntry::make('old_value')
                                    ->label('Old Value')
                                    ->placeholder('null')
                                    ->extraAttributes(['class' => 'rr-change-old-value']),

                                TextEntry::make('new_value')
                                    ->label('New Value')
                                    ->placeholder('null')
                                    ->extraAttributes(['class' => 'rr-change-new-value']),
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
