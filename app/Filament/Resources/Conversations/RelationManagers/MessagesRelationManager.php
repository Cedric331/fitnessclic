<?php

namespace App\Filament\Resources\Conversations\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    protected static ?string $title = 'Messages';

    protected static ?string $label = 'Message';

    protected static ?string $pluralLabel = 'Messages';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('body')
            ->columns([
                Tables\Columns\TextColumn::make('sender.name')
                    ->label('Expéditeur'),
                Tables\Columns\TextColumn::make('body')
                    ->label('Message')
                    ->wrap()
                    ->limit(200),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Envoyé le')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\IconColumn::make('read_at')
                    ->label('Lu')
                    ->boolean(),
            ])
            ->defaultSort('created_at', 'asc');
    }
}
