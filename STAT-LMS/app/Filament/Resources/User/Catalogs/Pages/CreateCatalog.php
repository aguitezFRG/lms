<?php

namespace App\Filament\Resources\User\Catalogs\Pages;

use App\Filament\Resources\User\Catalogs\CatalogResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCatalog extends CreateRecord
{
    protected static string $resource = CatalogResource::class;
}
