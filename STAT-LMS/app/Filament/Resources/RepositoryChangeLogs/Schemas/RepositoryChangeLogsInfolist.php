<?php

namespace App\Filament\Resources\RepositoryChangeLogs\Schemas;

use App\Enums\RepositoryChangeType;
use App\Models\RepositoryChangeLogs;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;

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
                    TextEntry::make('change_made')
                        ->label('Changes Made')
                        ->state(fn ($record) => $record->getRawOriginal('change_made'))
                        ->formatStateUsing(function ($state) {
                            if (!$state) return 'No changes recorded.';

                            $data = is_string($state) ? json_decode($state, true) : $state;

                            if (!is_array($data)) return 'No changes recorded.';

                            $cell = fn ($v): string => match (true) {
                                is_null($v)   => '<span class="italic text-gray-400">null</span>',
                                is_array($v)  => implode(', ', array_map('strval', $v)),
                                is_bool($v)   => $v ? 'true' : 'false',
                                default       => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'),
                            };

                            $rows = collect($data)
                                ->except(['id', 'created_at', 'updated_at'])
                                ->map(fn ($value, $key) =>
                                    "<tr>
                                        <td class='px-4 py-2 font-mono text-sm font-medium text-gray-700 dark:text-gray-300 w-1/4'>{$key}</td>
                                        <td class='px-4 py-2 text-sm text-danger-600 dark:text-danger-400 w-3/8'>{$cell($value['old'] ?? null)}</td>
                                        <td class='px-4 py-2 text-sm text-success-600 dark:text-success-400 w-3/8'>{$cell($value['new'] ?? null)}</td>
                                    </tr>"
                                )
                                ->join('');

                            return "
                                <table class='w-full border-collapse'>
                                    <thead>
                                        <tr class='border-b border-gray-200 dark:border-gray-700'>
                                            <th class='px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-1/4'>Field</th>
                                            <th class='px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-3/8'>Old Value</th>
                                            <th class='px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-3/8'>New Value</th>
                                        </tr>
                                    </thead>
                                    <tbody class='divide-y divide-gray-100 dark:divide-gray-800'>
                                        {$rows}
                                    </tbody>
                                </table>
                            ";
                        })
                        ->html()
                        ->columnSpanFull(),
                    ]),
            ]);
    }
}