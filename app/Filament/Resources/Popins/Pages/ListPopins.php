<?php

namespace App\Filament\Resources\Popins\Pages;

use App\Filament\Resources\Popins\PopinResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPopins extends ListRecords
{
    protected static string $resource = PopinResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

