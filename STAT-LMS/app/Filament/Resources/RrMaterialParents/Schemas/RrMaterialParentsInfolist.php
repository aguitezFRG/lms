<?php

namespace App\Filament\Resources\RrMaterialParents\Schemas;

use App\Models\RrMaterialParents;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class RrMaterialParentsInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('material_type')
                    ->numeric(),
                TextEntry::make('title'),
                TextEntry::make('abstract'),
                TextEntry::make('keywords'),
                TextEntry::make('sdgs')
                    ->placeholder('-'),
                TextEntry::make('publication_date')
                    ->date(),
                TextEntry::make('author')
                    ->numeric(),
                TextEntry::make('adviser')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('access_level')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (RrMaterialParents $record): bool => $record->trashed()),
            ]);
    }
}
