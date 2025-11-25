<?php

namespace App\Filament\Resources\Categories\RelationManagers;

use Filament\Actions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ExercisesRelationManager extends RelationManager
{
    protected static string $relationship = 'exercises';

    protected static ?string $title = 'Exercices';

    protected static ?string $label = 'Exercice';

    protected static ?string $pluralLabel = 'Exercices';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Titre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Créateur')
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
                Actions\AttachAction::make()
                    ->label('Attacher')
                    ->preloadRecordSelect(),
            ])
            ->actions([
                Actions\DetachAction::make()
                    ->label('Détacher'),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DetachBulkAction::make()
                        ->label('Détacher la sélection'),
                ]),
            ]);
    }
}

