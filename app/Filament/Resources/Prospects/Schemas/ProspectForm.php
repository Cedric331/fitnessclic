<?php

namespace App\Filament\Resources\Prospects\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProspectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->maxLength(255)
                    ->required(),

                Select::make('popin_id')
                    ->label('Popin')
                    ->relationship('popin', 'title')
                    ->searchable()
                    ->required(),
            ]);
    }
}

