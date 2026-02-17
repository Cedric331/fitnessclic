<?php

namespace App\Filament\Resources\BlogPosts\Schemas;

use App\Models\BlogPost;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class BlogPostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Titre')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, $set) {
                        if (! $state) {
                            return;
                        }

                        $set('slug', Str::slug($state));
                    })
                    ->columnSpanFull(),

                TextInput::make('slug')
                    ->label('Slug')
                    ->helperText('Généré automatiquement depuis le titre.')
                    ->disabled()
                    ->dehydrated()
                    ->maxLength(255)
                    ->columnSpanFull(),

                SpatieMediaLibraryFileUpload::make('banner')
                    ->label('Bannière')
                    ->collection(BlogPost::MEDIA_BANNER)
                    ->directory('blog')
                    ->image()
                    ->imageEditor()
                    ->preserveFilenames()
                    ->columnSpanFull(),

                RichEditor::make('content')
                    ->label('Contenu')
                    ->required()
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory('blog')
                    ->fileAttachmentsVisibility('public')
                    // ->resizableImages()
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
                        'blockquote',
                        'codeBlock',
                        'attachFiles',
                        'alignStart',
                        'alignCenter',
                        'alignEnd',
                    ])
                    ->extraAttributes(['class' => 'editor-content'])
                    ->columnSpanFull(),
                

                TagsInput::make('tags')
                    ->label('Tags')
                    ->placeholder('Ajouter un tag')
                    ->helperText('Appuyez sur Entrée pour ajouter un tag.')
                    ->columnSpanFull(),

                DateTimePicker::make('published_at')
                    ->label('Date de publication')
                    ->seconds(false),

                Toggle::make('is_published')
                    ->label('Publier cet article')
                    ->default(false),
            ]);
    }
}


