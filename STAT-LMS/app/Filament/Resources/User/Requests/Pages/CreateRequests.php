<?php

namespace App\Filament\Resources\User\Requests\Pages;

use App\Filament\Resources\User\Requests\RequestsResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRequests extends CreateRecord
{
    protected static string $resource = RequestsResource::class;
}
