<?php

namespace App\Filament\Resources\Announcements\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AnnouncementForm
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

                RichEditor::make('description')
                    ->label('Description')
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

                SpatieMediaLibraryFileUpload::make('image')
                    ->label('Image (optionnel)')
                    ->collection(\App\Models\Announcement::MEDIA_IMAGE)
                    ->directory('announcements')
                    ->image()
                    ->imageEditor()
                    ->preserveFilenames()
                    ->columnSpanFull(),

                Toggle::make('is_active')
                    ->label('Activer cette annonce')
                    ->helperText('Une seule annonce peut être active à la fois. Activer cette annonce désactivera automatiquement les autres.')
                    ->default(false),
            ]);
    }
}

