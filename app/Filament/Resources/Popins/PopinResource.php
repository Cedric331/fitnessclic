<?php

namespace App\Filament\Resources\Popins;

use App\Filament\Resources\Popins\Pages\CreatePopin;
use App\Filament\Resources\Popins\Pages\EditPopin;
use App\Filament\Resources\Popins\Pages\ListPopins;
use App\Filament\Resources\Popins\Schemas\PopinForm;
use App\Filament\Resources\Popins\Tables\PopinsTable;
use App\Models\Popin;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PopinResource extends Resource
{
    protected static ?string $model = Popin::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = 'Popins';

    protected static ?string $modelLabel = 'Popin';

    protected static ?string $pluralModelLabel = 'Popins';

    protected static UnitEnum|string|null $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return PopinForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PopinsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPopins::route('/'),
            'create' => CreatePopin::route('/create'),
            'edit' => EditPopin::route('/{record}/edit'),
        ];
    }
}

