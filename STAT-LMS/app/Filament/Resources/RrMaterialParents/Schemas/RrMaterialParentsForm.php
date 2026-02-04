<?php

namespace App\Filament\Resources\RrMaterialParents\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class RrMaterialParentsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('material_type')
                    ->required()
                    ->numeric(),
                TextInput::make('title')
                    ->required(),
                TextInput::make('abstract')
                    ->required(),
                TextInput::make('keywords')
                    ->required(),
                TextInput::make('sdgs'),
                DatePicker::make('publication_date')
                    ->required(),
                TextInput::make('author')
                    ->required()
                    ->numeric(),
                Textarea::make('adviser')
                    ->columnSpanFull(),
                TextInput::make('access_level')
                    ->required()
                    ->numeric(),
            ]);
    }
}
