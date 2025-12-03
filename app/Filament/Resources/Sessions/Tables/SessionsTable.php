<?php

namespace App\Filament\Resources\Sessions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Utilisateur')
                    ->searchable(),
                TextColumn::make('customers.full_name')
                    ->label('Clients')
                    ->badge()
                    ->separator(',')
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                TextColumn::make('session_date')
                    ->label('Date de la séance')
                    ->date()
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
                SelectFilter::make('user_id')
                    ->label('Utilisateur')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('customers')
                    ->label('Client')
                    ->relationship('customers', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name}")
                    ->searchable()
                    ->preload(),
                Filter::make('session_date')
                    ->label('Date de la séance')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('session_date_from')
                            ->label('Du'),
                        \Filament\Forms\Components\DatePicker::make('session_date_until')
                            ->label('Au'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['session_date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('session_date', '>=', $date),
                            )
                            ->when(
                                $data['session_date_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('session_date', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Modifier'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Supprimer la sélection'),
                ]),
            ]);
    }
}
