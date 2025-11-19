<?php

namespace App\Filament\Resources\Users\RelationManagers;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ExercisesRelationManager extends RelationManager
{
    protected static string $relationship = 'exercises';

    protected static ?string $title = 'Exercices';

    protected static ?string $label = 'Exercice';

    protected static ?string $pluralLabel = 'Exercices';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('title')
                    ->label('Titre')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label('Description')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('suggested_duration')
                    ->label('Durée suggérée')
                    ->maxLength(255),
                Forms\Components\Toggle::make('is_shared')
                    ->label('Partagé')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Titre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('suggested_duration')
                    ->label('Durée suggérée')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_shared')
                    ->label('Partagé')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_shared')
                    ->label('Partage')
                    ->placeholder('Tous')
                    ->trueLabel('Partagés')
                    ->falseLabel('Non partagés')
                    ->boolean(),
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

