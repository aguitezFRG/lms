<?php

namespace App\Filament\Resources\RrMaterials\Schemas;

use App\Models\RrMaterials;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class RrMaterialsInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('material_parent_id')
                    ->numeric(),
                IconEntry::make('is_digital')
                    ->boolean(),
                IconEntry::make('is_available')
                    ->boolean(),
                TextEntry::make('file_name'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (RrMaterials $record): bool => $record->trashed()),
            ]);
    }
}
