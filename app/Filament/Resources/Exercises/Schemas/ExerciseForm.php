<?php

namespace App\Filament\Resources\Exercises\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

class ExerciseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                SpatieMediaLibraryFileUpload::make('image')
                    ->label('Image')
                    ->collection(\App\Models\Exercise::MEDIA_IMAGE)
                    ->disk(\App\Models\Exercise::MEDIA_DISK)
                    ->image()
                    ->preserveFilenames()
                    ->columnSpanFull()                    
                    ->required(),

                Select::make('user_id')
                    ->label('Utilisateur')
                    ->relationship('user', 'name')
                    ->required(),

                TextInput::make('title')
                    ->label('Titre')
                    ->required(),

                Textarea::make('description')
                    ->label('Description')
                    ->columnSpanFull(),

                TextInput::make('suggested_duration')
                    ->columnSpanFull()                    
                    ->label('Durée suggérée'),

                Toggle::make('is_shared')
                    ->label('Partagé')
                    ->default(true)
                    ->required(),
            ]);
    }
}
