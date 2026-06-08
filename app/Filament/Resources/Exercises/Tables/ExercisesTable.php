<?php

namespace App\Filament\Resources\Exercises\Tables;

use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class ExercisesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Utilisateur')
                    ->searchable(),
                TextColumn::make('title')
                    ->label('Titre')
                    ->searchable(),
                TextColumn::make('suggested_duration')
                    ->label('Durée suggérée')
                    ->searchable(),
                IconColumn::make('is_shared')
                    ->label('Partagé')
                    ->boolean(),
                IconColumn::make('is_premium')
                    ->label('Premium')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Modifié le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('Utilisateur')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_shared')
                    ->label('Partage')
                    ->placeholder('Tous')
                    ->trueLabel('Partagés')
                    ->falseLabel('Non partagés')
                    ->boolean(),
                TernaryFilter::make('is_premium')
                    ->label('Premium')
                    ->placeholder('Tous')
                    ->trueLabel('Premium')
                    ->falseLabel('Gratuits')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Modifier'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('mark_premium')
                        ->label('Passer en Premium')
                        ->icon('heroicon-o-star')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Passer en Premium')
                        ->modalDescription('Seuls vos exercices seront affectés. Les exercices appartenant à d\'autres utilisateurs seront ignorés.')
                        ->modalSubmitActionLabel('Confirmer')
                        ->action(function (Collection $records) {
                            $userId = Auth::id();
                            $owned = $records->filter(fn ($r) => $r->user_id === $userId);
                            $skipped = $records->count() - $owned->count();

                            $owned->each->update(['is_premium' => true]);

                            Notification::make()
                                ->title($owned->count().' exercice(s) passés en Premium'.($skipped ? " ({$skipped} ignoré(s) appartiennent à d'autres utilisateurs)" : ''))
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('mark_free')
                        ->label('Passer en Gratuit')
                        ->icon('heroicon-o-lock-open')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Passer en Gratuit')
                        ->modalDescription('Seuls vos exercices seront affectés. Les exercices appartenant à d\'autres utilisateurs seront ignorés.')
                        ->modalSubmitActionLabel('Confirmer')
                        ->action(function (Collection $records) {
                            $userId = Auth::id();
                            $owned = $records->filter(fn ($r) => $r->user_id === $userId);
                            $skipped = $records->count() - $owned->count();

                            $owned->each->update(['is_premium' => false]);

                            Notification::make()
                                ->title($owned->count().' exercice(s) passés en Gratuit'.($skipped ? " ({$skipped} ignoré(s) appartiennent à d'autres utilisateurs)" : ''))
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make()
                        ->label('Supprimer la sélection'),
                ]),
            ]);
    }
}
