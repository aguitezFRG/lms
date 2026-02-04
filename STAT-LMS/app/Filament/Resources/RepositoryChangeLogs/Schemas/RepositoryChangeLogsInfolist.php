<?php

namespace App\Filament\Resources\RepositoryChangeLogs\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class RepositoryChangeLogsInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('editor_id')
                    ->numeric(),
                TextEntry::make('rr_material_id')
                    ->numeric(),
                TextEntry::make('target_user_id')
                    ->numeric(),
                TextEntry::make('table_changed'),
                TextEntry::make('change_type'),
                TextEntry::make('change_made')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('changed_at')
                    ->dateTime(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
