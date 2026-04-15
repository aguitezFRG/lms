<?php

namespace App\Filament\Resources\RrMaterialParents\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;

class RrMaterialParentsInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Material Overview')
                    ->columnSpanFull()
                    ->components([
                        TextEntry::make('title')
                            ->label('Title')
                            ->columnSpanFull()
                            ->weight('bold')
                            ->size('lg'),
                        TextEntry::make('abstract')
                            ->label('Abstract')
                            ->columnSpanFull()
                            ->prose()
                            ->markdown(),
                        Grid::make(3)
                            ->components([
                                TextEntry::make('material_type')
                                    ->badge()
                                    ->colors(['primary' => 1, 'success' => 2, 'warning' => 3, 'danger' => 4, 'gray' => 5])
                                    ->formatStateUsing(fn (int $state) => match ($state) {
                                        1 => 'Book', 2 => 'Thesis', 3 => 'Journal', 4 => 'Dissertation', 5 => 'Others', default => 'Unknown'
                                    }),
                                TextEntry::make('publication_date')->date('F d, Y'),
                                TextEntry::make('access_level')
                                    ->badge()
                                    ->color(fn (int $state): string => match ($state) {
                                    1 => 'success', // Public (UP Forest Green)
                                    2 => 'danger',  // Restricted (Red)
                                    3 => 'gray',    // Confidential (Black/Dark Gray)
                                    default => 'gray',
                                })
                                ->formatStateUsing(fn (int $state): string => match ($state) {
                                    1 => 'Public',
                                    2 => 'Restricted',
                                    3 => 'Confidential',
                                    default => 'Unknown',
                                }),
                            ]),
                    ]),

                Section::make('Authorship & Research Metadata')
                    ->components([
                        TextEntry::make('author')
                            ->label('Primary Author')
                            ->formatStateUsing(fn ($record) => $record->authorUser?->name ?? $record->author),
                        TextEntry::make('adviser')
                            ->badge()
                            ->color('success')
                            ->separator(', '),
                        TextEntry::make('keywords')
                            ->badge()
                            ->color('gray')
                            ->separator(', '),
                        TextEntry::make('sdgs')
                            ->label('SDGs')
                            ->badge()
                            ->color('warning')
                            ->separator(', '),
                    ])->columns(2),

                Section::make('System Metadata')
                    ->components([
                        TextEntry::make('id')
                            ->label('Internal UUID')
                            ->copyable(),
                        TextEntry::make('created_at')
                            ->label('Registered On')
                            ->dateTime('F d, Y h:i A'),
                        TextEntry::make('updated_at')
                            ->label('Last Modified')
                            ->dateTime('F d, Y h:i A'),
                        TextEntry::make('deleted_at')
                            ->label('Soft Deleted At')
                            ->dateTime('F d, Y h:i A')
                            ->placeholder('Active Material')
                            ->color('danger'),
                    ])->columns(2),
            ]);
    }
}