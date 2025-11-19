<?php

namespace App\Filament\Resources\Exercises\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sessions';

    protected static ?string $title = 'Séances';

    protected static ?string $label = 'Séance';

    protected static ?string $pluralLabel = 'Séances';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom de la séance')
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer.full_name')
                    ->label('Client')
                    ->searchable(['first_name', 'last_name']),
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
                    ->sortable(),
                Tables\Columns\TextColumn::make('session_date')
                    ->label('Date de la séance')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Attacher')
                    ->preloadRecordSelect()
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect(),
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
                Tables\Actions\EditAction::make()
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
                Tables\Actions\DetachAction::make()
                    ->label('Détacher'),
            ])
            ->toolbarActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()
                        ->label('Détacher la sélection'),
                ]),
            ]);
    }
}

