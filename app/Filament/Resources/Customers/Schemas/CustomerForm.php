<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Utilisateur')
                    ->relationship('user', 'name')
                    ->required(),
                TextInput::make('first_name')
                    ->label('Prénom')
                    ->required(),
                TextInput::make('last_name')
                    ->label('Nom')
                    ->required(),
                TextInput::make('email')
                    ->label('Adresse email')
                    ->email(),
                TextInput::make('phone')
                    ->label('Téléphone')
                    ->tel(),
                Textarea::make('internal_note')
                    ->label('Note interne')
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('Actif')
                    ->required(),
            ]);
    }
}
