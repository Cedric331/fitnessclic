<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
                        UserRole::CUSTOMER->value => 'Client',
                    ])
                    ->default(UserRole::CUSTOMER->value)
                    ->required(),
                DateTimePicker::make('email_verified_at')
                    ->label('Email vérifié le'),
                TextInput::make('password')
                    ->label('Mot de passe')
                    ->password()
                    ->required(),
                // Textarea::make('two_factor_secret')
                //     ->columnSpanFull(),
                // Textarea::make('two_factor_recovery_codes')
                //     ->columnSpanFull(),
                // DateTimePicker::make('two_factor_confirmed_at'),
            ]);
    }
}
