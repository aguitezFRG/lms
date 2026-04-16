<?php

namespace App\Filament\Resources\RrMaterials\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;

class RrMaterialsInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Material Details')
                ->columnSpanFull()
                ->components([
                    TextEntry::make('parent.title')
                        ->label('Parent Resource'),

                    TextEntry::make('parent.abstract')
                        ->label('Abstract')
                        ->prose()
                        ->markdown(),

                    Grid::make(3)->schema([
                        TextEntry::make('parent.publication_date')
                            ->label('Published')
                            ->date('F d, Y'),

                        TextEntry::make('parent.author')
                            ->label('Primary Author')
                            ->formatStateUsing(fn ($record) => $record->parent->authorUser?->name ?? $record->parent->author),

                        TextEntry::make('parent.adviser')
                            ->label('Adviser(s)')
                            ->badge()
                            ->color('success')
                            ->separator(', '),
                    ]),

                    Grid::make(3)->schema([
                        TextEntry::make('parent.keywords')
                            ->badge()
                            ->separator(', '),

                        TextEntry::make('parent.sdgs')
                            ->label('SDGs')
                            ->badge()
                            ->color('warning')
                            ->separator(', '),
                    ]),

                    Grid::make(2)->schema([
                        TextEntry::make('is_digital')
                            ->label('Format')
                            ->badge()
                            ->color(fn ($state) => $state ? 'success' : 'gray')
                            ->formatStateUsing(fn ($state) => $state ? 'Digital' : 'Physical'),

                        TextEntry::make('parent.access_level')
                            ->label('Access Level')
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

                    TextEntry::make('is_available')
                        ->label('Circulation Status')
                        ->badge()
                        ->color(fn ($state) => $state ? 'success' : 'danger')
                        ->formatStateUsing(fn ($state) => $state ? 'Available' : 'Restricted'),
                ]),

            Section::make('System Metadata')
                ->columnSpanFull()
                ->components([
                    TextEntry::make('id')->label('Internal UUID')->copyable(),
                    TextEntry::make('created_at')->label('Added At')->dateTime(),
                    TextEntry::make('updated_at')->label('Last Modified')->dateTime(),
                    TextEntry::make('deleted_at')->label('Removed At')->placeholder('Active')->dateTime(),
                ])->columns(2),
        ]);
    }
}