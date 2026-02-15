<?php

namespace App\Filament\Resources\Popins\Schemas;

use App\Models\Popin;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PopinForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Contenu')
                    ->schema([
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
                    ])
                    ->columns(1),

                Section::make('Image')
                    ->schema([
                        Select::make('image_size')
                            ->label('Taille de l\'image')
                            ->options([
                                'small' => 'Petite',
                                'medium' => 'Moyenne',
                                'large' => 'Grande',
                            ])
                            ->default('medium')
                            ->required(),

                        SpatieMediaLibraryFileUpload::make('image')
                            ->label('Image (optionnel)')
                            ->collection(Popin::MEDIA_IMAGE)
                            ->directory('popins')
                            ->image()
                            ->imageEditor()
                            ->imageEditorViewportWidth(1200)
                            ->imageEditorViewportHeight(675)
                            ->imageEditorAspectRatios([
                                null,
                                '16:9',
                                '4:3',
                                '1:1',
                            ])
                            ->preserveFilenames()
                            ->columnSpanFull(),
                    ])
                    ->columns(1),

                Section::make('Offre')
                    ->schema([
                        TextInput::make('promo_code')
                            ->label('Code promo')
                            ->maxLength(100)
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->columns(1),

                Section::make('Paramètres')
                    ->schema([
                        TextInput::make('delay_seconds')
                            ->label('Délai d\'affichage (secondes)')
                            ->helperText('Le délai avant l\'affichage de la popin pour les visiteurs non connectés.')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),

                        Toggle::make('is_active')
                            ->label('Activer cette popin')
                            ->helperText('Une seule popin peut être active à la fois. Activer cette popin désactivera automatiquement les autres.')
                            ->default(false),
                    ])
                    ->columns(1),
            ]);
    }
}

