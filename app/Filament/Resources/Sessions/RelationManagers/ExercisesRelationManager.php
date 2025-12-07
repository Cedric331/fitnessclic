<?php

namespace App\Filament\Resources\Sessions\RelationManagers;

use App\Models\Exercise;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ExercisesRelationManager extends RelationManager
{
    protected static string $relationship = 'sessionExercises';

    protected static ?string $title = 'Exercices';

    protected static ?string $label = 'Exercice';

    protected static ?string $pluralLabel = 'Exercices';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('exercise_id')
                    ->label('Exercice')
                    ->relationship('exercise', 'title')
                    ->searchable()
                    ->required()
                    ->preload()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $exercise = Exercise::find($state);
                            if ($exercise) {
                                $set('custom_exercise_name', $exercise->title);
                            }
                        }
                    }),
                Forms\Components\TextInput::make('custom_exercise_name')
                    ->label('Nom personnalisé')
                    ->maxLength(255)
                    ->helperText('Laissez vide pour utiliser le nom de l\'exercice'),
                Forms\Components\TextInput::make('repetitions')
                    ->label('Répétitions')
                    ->numeric()
                    ->visible(fn (callable $get) => !$get('use_duration')),
                Forms\Components\TextInput::make('duration')
                    ->label('Durée')
                    ->maxLength(255)
                    ->visible(fn (callable $get) => $get('use_duration')),
                Forms\Components\Toggle::make('use_duration')
                    ->label('Utiliser la durée au lieu des répétitions')
                    ->default(false)
                    ->reactive(),
                Forms\Components\TextInput::make('weight')
                    ->label('Charge (kg)')
                    ->numeric()
                    ->step(0.01)
                    ->visible(fn (callable $get) => !$get('use_bodyweight')),
                Forms\Components\Toggle::make('use_bodyweight')
                    ->label('Poids de corps')
                    ->default(false)
                    ->reactive(),
                Forms\Components\TextInput::make('rest_time')
                    ->label('Temps de repos')
                    ->maxLength(255)
                    ->placeholder('Ex: 60s, 2min'),
                Forms\Components\TextInput::make('sets_count')
                    ->label('Nombre de séries')
                    ->numeric()
                    ->default(1)
                    ->minValue(1),
                Forms\Components\Textarea::make('additional_description')
                    ->label('Description supplémentaire')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('order')
                    ->label('Ordre')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Section::make('Séries multiples')
                    ->schema([
                        Repeater::make('sets')
                            ->label('Séries')
                            ->relationship('sets')
                            ->schema([
                                Forms\Components\TextInput::make('set_number')
                                    ->label('Numéro de série')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(1),
                                Forms\Components\TextInput::make('repetitions')
                                    ->label('Répétitions')
                                    ->numeric(),
                                Forms\Components\TextInput::make('weight')
                                    ->label('Charge (kg)')
                                    ->numeric()
                                    ->step(0.01),
                                Forms\Components\TextInput::make('rest_time')
                                    ->label('Temps de repos')
                                    ->maxLength(255)
                                    ->placeholder('Ex: 60s, 2min'),
                                Forms\Components\TextInput::make('duration')
                                    ->label('Durée')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('order')
                                    ->label('Ordre')
                                    ->numeric()
                                    ->default(0)
                                    ->required(),
                            ])
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => 'Série ' . ($state['set_number'] ?? '?')),
                    ])
                    ->collapsible()
                    ->collapsed(),
                Section::make('Super Set')
                    ->schema([
                        Forms\Components\TextInput::make('block_id')
                            ->label('ID du bloc')
                            ->numeric()
                            ->helperText('ID partagé pour regrouper les exercices en super set'),
                        Forms\Components\Select::make('block_type')
                            ->label('Type de bloc')
                            ->options([
                                'standard' => 'Standard',
                                'set' => 'Super Set',
                            ])
                            ->default('standard'),
                        Forms\Components\TextInput::make('position_in_block')
                            ->label('Position dans le bloc')
                            ->numeric()
                            ->default(0)
                            ->helperText('Position de l\'exercice dans le super set'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('exercise.title')
            ->columns([
                Tables\Columns\TextColumn::make('exercise.title')
                    ->label('Exercice')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('custom_exercise_name')
                    ->label('Nom personnalisé')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('repetitions')
                    ->label('Répétitions')
                    ->numeric()
                    ->toggleable()
                    ->visible(fn ($record) => !$record->use_duration),
                Tables\Columns\TextColumn::make('duration')
                    ->label('Durée')
                    ->toggleable()
                    ->visible(fn ($record) => $record->use_duration),
                Tables\Columns\TextColumn::make('weight')
                    ->label('Charge (kg)')
                    ->numeric(decimalPlaces: 2)
                    ->toggleable()
                    ->visible(fn ($record) => !$record->use_bodyweight),
                Tables\Columns\IconColumn::make('use_bodyweight')
                    ->label('Poids de corps')
                    ->boolean()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('rest_time')
                    ->label('Temps de repos')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('sets_count')
                    ->label('Nombre de séries')
                    ->numeric()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('sets')
                    ->label('Sets multiples')
                    ->counts('sets')
                    ->formatStateUsing(fn ($state) => $state > 0 ? $state : '-')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('block_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'set' ? 'Super Set' : 'Standard')
                    ->color(fn ($state) => $state === 'set' ? 'success' : 'gray')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('order')
                    ->label('Ordre')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('block_type')
                    ->label('Type de bloc')
                    ->options([
                        'standard' => 'Standard',
                        'set' => 'Super Set',
                    ]),
                Tables\Filters\TernaryFilter::make('use_bodyweight')
                    ->label('Poids de corps')
                    ->placeholder('Tous')
                    ->trueLabel('Oui')
                    ->falseLabel('Non'),
                Tables\Filters\TernaryFilter::make('use_duration')
                    ->label('Mode durée')
                    ->placeholder('Tous')
                    ->trueLabel('Oui')
                    ->falseLabel('Non'),
            ])
            ->headerActions([
                Actions\CreateAction::make()
                    ->label('Ajouter'),
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
            ])
            ->defaultSort('order', 'asc');
    }
}

