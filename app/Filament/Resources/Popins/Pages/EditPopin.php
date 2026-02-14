<?php

namespace App\Filament\Resources\Popins\Pages;

use App\Filament\Resources\Popins\PopinResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPopin extends EditRecord
{
    protected static string $resource = PopinResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

