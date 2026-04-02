<?php

namespace App\Filament\Resources\RrMaterials\Schemas;

use App\Models\RrMaterialParents;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Str;

class RrMaterialsForm
{
    public static function configure($schema)
    {
        return $schema->schema([
            Section::make('Copy Specification')
                ->columnSpanFull()
                ->schema([
                    Select::make('material_parent_id')
                        ->label('Parent Material')
                        ->relationship('parent', 'title')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->columnSpanFull(),

                    Toggle::make('is_digital')
                        ->label('Digital Copy')
                        ->default(true)
                        ->live(),

                    Toggle::make('is_available')
                        ->label('Available for Circulation')
                        ->default(true),
                ])->columns(3),

            Section::make('Repository Information')
                ->columnSpanFull()
                ->schema([
                    // DIGITAL UPLOAD BLOCK
                    FileUpload::make('file_name')
                        ->label('Digital File (PDF)')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(10240) // 10 MB in kilobytes
                        ->validationMessages([
                            'max'   => 'The file must not exceed 10 MB. Please compress the PDF or split it before uploading.',
                            'mimes' => 'Only PDF files are accepted. Please convert your document before uploading.',
                        ])
                        ->hint('PDF only · Maximum 10 MB')
                        ->hintColor('gray')
                        ->visible(fn (Get $get) => $get('is_digital'))
                        ->required(fn (Get $get) => $get('is_digital'))
                        ->disk('local')
                        ->directory(function (Get $get) {
                            $parent = RrMaterialParents::find($get('material_parent_id'));
                            $accessLevel = $parent?->access_level ?? 'unclassified';
                            return "repository/access_level_{$accessLevel}";
                        })
                        ->getUploadedFileNameForStorageUsing(function ($file, Get $get) {
                            $parent = RrMaterialParents::find($get('material_parent_id'));
                            $title = Str::slug($parent?->title ?? 'unknown');
                            $year = $parent?->publication_date?->format('Y') ?? date('Y');
                            $uuid = (string) Str::uuid();
                            $version = 'v1';

                            $typePrefix = match($parent?->material_type) {
                                1 => 'book', 2 => 'thesis', 3 => 'journal',
                                4 => 'dissertation', default => 'other'
                            };

                            $rawName = "{$title}-{$year}-{$uuid}-{$version}";
                            return $typePrefix . '_' . $rawName . '.' . $file->getClientOriginalExtension();
                        }),

                    /* // PHYSICAL COPY METADATA (Commented out for future use)
                    TextInput::make('physical_location')
                        ->label('Shelf/Cabinet Location')
                        ->visible(fn (Get $get) => ! $get('is_digital'))
                        ->placeholder('e.g. Shelf A-12'),
                    */
                ]),
        ]);
    }
}