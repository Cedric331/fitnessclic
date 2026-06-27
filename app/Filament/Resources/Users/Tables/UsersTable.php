<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserRole;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use App\Filament\Exports\UserExporter;
use Filament\Actions\ExportAction;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Adresse email')
                    ->searchable(),
                TextColumn::make('role')
                    ->label('Rôle')
                    ->badge()
                    ->formatStateUsing(fn (UserRole $state): string => $state->label()),
                TextColumn::make('email_verified_at')
                    ->label('Email vérifié le')
                    ->dateTime()
                    ->sortable(),
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
                SelectFilter::make('role')
                    ->label('Rôle')
                    ->options([
                        UserRole::ADMIN->value => 'Administrateur',
                        UserRole::COACH->value => 'Coach',
                        UserRole::CLIENT->value => 'Client',
                    ]),
                TernaryFilter::make('email_verified_at')
                    ->label('Email vérifié')
                    ->placeholder('Tous')
                    ->trueLabel('Vérifiés')
                    ->falseLabel('Non vérifiés')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('email_verified_at'),
                        false: fn ($query) => $query->whereNull('email_verified_at'),
                        blank: fn ($query) => $query,
                    ),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Modifier'),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(UserExporter::class),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Supprimer la sélection'),
                ]),
            ]);
    }
}
