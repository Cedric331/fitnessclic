<?php

namespace App\Filament\Resources\Popins\Schemas;

use App\Models\Popin;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PopinForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Titre')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                RichEditor::make('content')
                    ->label('Contenu')
                    ->required()
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'underline',
                        'strike',
                        'link',
                        'orderedList',
                        'bulletList',
                        'h2',
                        'h3',
                    ])
                    ->columnSpanFull(),

                TextInput::make('promo_code')
                    ->label('Code promo')
                    ->maxLength(100)
                    ->required()
                    ->columnSpanFull(),

                TextInput::make('delay_seconds')
                    ->label('Délai d\'affichage (secondes)')
                    ->helperText('Le délai avant l\'affichage de la popin pour les visiteurs non connectés.')
                    ->numeric()
                    ->minValue(0)
                    ->default(0),

                SpatieMediaLibraryFileUpload::make('image')
                    ->label('Image (optionnel)')
                    ->collection(Popin::MEDIA_IMAGE)
                    ->directory('popins')
                    ->image()
                    ->imageEditor()
                    ->preserveFilenames()
                    ->columnSpanFull(),

                Toggle::make('is_active')
                    ->label('Activer cette popin')
                    ->helperText('Une seule popin peut être active à la fois. Activer cette popin désactivera automatiquement les autres.')
                    ->default(false),
            ]);
    }
}

