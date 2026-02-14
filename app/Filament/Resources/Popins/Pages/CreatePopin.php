<?php

namespace App\Filament\Resources\Popins\Pages;

use App\Filament\Resources\Popins\PopinResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePopin extends CreateRecord
{
    protected static string $resource = PopinResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

