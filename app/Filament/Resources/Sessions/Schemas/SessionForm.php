<?php

namespace App\Filament\Resources\Sessions\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Schema;

class SessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Utilisateur')
                    ->relationship('user', 'name')
                    ->required(),
                CheckboxList::make('customers')
                    ->label('Clients')
                    ->relationship('customers', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name}")
                    ->searchable()
                    ->columns(2),
                TextInput::make('name')
                    ->label('Nom'),
                Textarea::make('notes')
                    ->label('Notes')
                    ->columnSpanFull(),
                DatePicker::make('session_date')
                    ->label('Date de la séance'),
            ]);
    }
}
