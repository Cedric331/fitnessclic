<?php

namespace App\Filament\Resources\Sessions\RelationManagers;

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
                Forms\Components\Select::make('exercise_id')
                    ->label('Exercice')
                    ->relationship('exercises', 'title')
                    ->searchable()
                    ->required()
                    ->preload(),
                Forms\Components\TextInput::make('repetitions')
                    ->label('Répétitions')
                    ->numeric(),
                Forms\Components\TextInput::make('rest_time')
                    ->label('Temps de repos')
                    ->maxLength(255),
                Forms\Components\TextInput::make('duration')
                    ->label('Durée')
                    ->maxLength(255),
                Forms\Components\Textarea::make('additional_description')
                    ->label('Description supplémentaire')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('order')
                    ->label('Ordre')
                    ->numeric()
                    ->default(0),
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
                Tables\Columns\TextColumn::make('pivot.repetitions')
                    ->label('Répétitions')
                    ->numeric(),
                Tables\Columns\TextColumn::make('pivot.rest_time')
                    ->label('Temps de repos'),
                Tables\Columns\TextColumn::make('pivot.duration')
                    ->label('Durée'),
                Tables\Columns\TextColumn::make('pivot.order')
                    ->label('Ordre')
                    ->numeric()
                    ->sortable(query: function ($query, string $direction) {
                        return $query->orderBy('session_exercise.order', $direction);
                    }),
                Tables\Columns\IconColumn::make('is_shared')
                    ->label('Partagé')
                    ->boolean(),
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
                Actions\AttachAction::make()
                    ->label('Attacher')
                    ->preloadRecordSelect()
                    ->form(fn (Forms\Components\Select $select): array => [
                        $select,
                        Forms\Components\TextInput::make('repetitions')
                            ->label('Répétitions')
                            ->numeric(),
                        Forms\Components\TextInput::make('rest_time')
                            ->label('Temps de repos')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('duration')
                            ->label('Durée')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('additional_description')
                            ->label('Description supplémentaire')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('order')
                            ->label('Ordre')
                            ->numeric()
                            ->default(0),
                    ]),
            ])
            ->actions([
                Actions\EditAction::make()
                    ->label('Modifier')
                    ->form([
                        Forms\Components\TextInput::make('repetitions')
                            ->label('Répétitions')
                            ->numeric(),
                        Forms\Components\TextInput::make('rest_time')
                            ->label('Temps de repos')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('duration')
                            ->label('Durée')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('additional_description')
                            ->label('Description supplémentaire')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('order')
                            ->label('Ordre')
                            ->numeric()
                            ->default(0),
                    ]),
                Actions\DetachAction::make()
                    ->label('Détacher'),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DetachBulkAction::make()
                        ->label('Détacher la sélection'),
                ]),
            ])
            ->modifyQueryUsing(function ($query) {
                return $query->orderBy('session_exercise.order', 'asc');
            });
    }
}

