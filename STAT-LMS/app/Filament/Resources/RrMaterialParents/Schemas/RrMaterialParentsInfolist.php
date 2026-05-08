<?php

namespace App\Filament\Resources\RrMaterialParents\Schemas;

use App\Filament\Resources\User\Catalogs\CatalogResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class RrMaterialParentsInfolist
{
    public static function configure(Schema $schema, bool $linkMetadata = false): Schema
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
                                    })
                                    ->url($linkMetadata
                                        ? fn ($record): string => CatalogResource::getUrl('index', ['typeFilter' => (string) $record->material_type])
                                        : null),
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
                            ->formatStateUsing(function ($record) use ($linkMetadata): HtmlString {
                                $author = $record->authorUser?->name ?? $record->author;

                                return self::catalogSearchLink($author, 'author', $linkMetadata);
                            })
                            ->html(),
                        TextEntry::make('adviser')
                            ->badge()
                            ->color('success')
                            ->formatStateUsing(fn ($state) => self::catalogArrayLinks($state, 'adviserFilter', linkMetadata: $linkMetadata))
                            ->html(),
                        TextEntry::make('keywords')
                            ->badge()
                            ->color('gray')
                            ->formatStateUsing(fn ($state) => self::catalogArrayLinks($state, 'search', ['searchScope' => 'keyword'], $linkMetadata))
                            ->html(),
                        TextEntry::make('sdgs')
                            ->label('SDGs')
                            ->badge()
                            ->color('warning')
                            ->formatStateUsing(fn ($state) => self::catalogArrayLinks($state, 'sdgFilter', linkMetadata: $linkMetadata))
                            ->html(),
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

    private static function catalogSearchLink(?string $value, string $scope, bool $linkMetadata): HtmlString
    {
        if (! $value) {
            return new HtmlString('');
        }

        if (! $linkMetadata) {
            return new HtmlString(e($value));
        }

        $url = CatalogResource::getUrl('index', [
            'search' => $value,
            'searchScope' => $scope,
        ]);

        return new HtmlString('<a href="'.e($url).'" class="underline">'.e($value).'</a>');
    }

    private static function catalogArrayLinks(mixed $state, string $param, array $extra = [], bool $linkMetadata = false): HtmlString
    {
        $values = array_values(array_filter(is_array($state) ? $state : [$state]));
        if ($values === []) {
            return new HtmlString('');
        }

        if (! $linkMetadata) {
            return new HtmlString(implode(', ', array_map(fn ($value): string => e((string) $value), $values)));
        }

        $links = array_map(function ($value) use ($param, $extra): string {
            $params = $extra;
            $params[$param] = $param === 'sdgFilter' ? [(string) $value] : (string) $value;
            $url = CatalogResource::getUrl('index', $params);

            return '<a href="'.e($url).'" class="underline">'.e((string) $value).'</a>';
        }, $values);

        return new HtmlString(implode(', ', $links));
    }
}
