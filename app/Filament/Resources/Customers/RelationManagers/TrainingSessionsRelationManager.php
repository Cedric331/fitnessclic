<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class TrainingSessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'trainingSessions';

    protected static ?string $title = 'Séances d\'entraînement';

    protected static ?string $label = 'Séance';

    protected static ?string $pluralLabel = 'Séances';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('user_id')
                    ->label('Utilisateur')
                    ->relationship('user', 'name')
                    ->required()
                    ->preload(),
                Forms\Components\TextInput::make('name')
                    ->label('Nom')
                    ->maxLength(255),
                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->columnSpanFull(),
                Forms\Components\DatePicker::make('session_date')
                    ->label('Date de la séance'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Utilisateur')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                Tables\Columns\TextColumn::make('session_date')
                    ->label('Date de la séance')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Utilisateur')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->headerActions([
                Actions\CreateAction::make()
                    ->label('Créer'),
            ])
            ->actions([
                Actions\EditAction::make()
                    ->label('Modifier'),
                Actions\DeleteAction::make()
                    ->label('Supprimer'),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()
                        ->label('Supprimer la sélection'),
                ]),
            ]);
    }
}

