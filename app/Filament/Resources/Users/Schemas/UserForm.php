<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nom')
                    ->required(),
                TextInput::make('email')
                    ->label('Adresse email')
                    ->email()
                    ->required(),
                Select::make('role')
                    ->label('Rôle')
                    ->options([
                        UserRole::ADMIN->value => 'Administrateur',
                        UserRole::COACH->value => 'Coach',
                        UserRole::CLIENT->value => 'Client',
                    ])
                    ->default(UserRole::COACH->value)
                    ->required(),
                DateTimePicker::make('email_verified_at')
                    ->label('Email vérifié le'),
                TextInput::make('password')
                    ->label('Mot de passe')
                    ->password()
                    ->required(),
            ]);
    }
}
