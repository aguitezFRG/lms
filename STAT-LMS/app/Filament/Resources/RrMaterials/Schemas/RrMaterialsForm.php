<?php

namespace App\Filament\Resources\RrMaterials\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class RrMaterialsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('material_parent_id')
                    ->required()
                    ->numeric(),
                Toggle::make('is_digital')
                    ->required(),
                Toggle::make('is_available')
                    ->required(),
                TextInput::make('file_name')
                    ->required(),
            ]);
    }
}
