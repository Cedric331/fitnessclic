<?php

namespace App\Filament\Resources\Prospects\Tables;

use App\Filament\Exports\ProspectExporter;
use Filament\Actions\ExportAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProspectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('popin.title')
                    ->label('Popin')
                    ->sortable()
                    ->limit(40),

                TextColumn::make('promo_code_sent_at')
                    ->label('Code envoyé')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Non envoyé'),

                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('popin')
                    ->label('Popin')
                    ->relationship('popin', 'title'),
            ])
            ->recordActions([])
            ->headerActions([
                ExportAction::make()
                    ->exporter(ProspectExporter::class),
            ]);
    }
}

