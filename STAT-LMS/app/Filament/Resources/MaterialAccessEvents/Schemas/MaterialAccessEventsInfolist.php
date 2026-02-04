<?php

namespace App\Filament\Resources\MaterialAccessEvents\Schemas;

use App\Models\MaterialAccessEvents;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class MaterialAccessEventsInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user_id')
                    ->numeric(),
                TextEntry::make('rr_material_id')
                    ->numeric(),
                TextEntry::make('approver_id')
                    ->numeric(),
                TextEntry::make('event_type'),
                TextEntry::make('status'),
                TextEntry::make('due_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('returned_at')
                    ->dateTime()
                    ->placeholder('-'),
                IconEntry::make('is_overdue')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('approved_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('completed_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (MaterialAccessEvents $record): bool => $record->trashed()),
            ]);
    }
}
