<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nom')
                    ->required(),
                Select::make('type')
                    ->label('Type')
                    ->options([
                        'public' => 'Publique',
                        'private' => 'Privée',
                    ])
                    ->default('private')
                    ->required(),
                Select::make('user_id')
                    ->label('Utilisateur')
                    ->relationship('user', 'name'),
            ]);
    }
}
