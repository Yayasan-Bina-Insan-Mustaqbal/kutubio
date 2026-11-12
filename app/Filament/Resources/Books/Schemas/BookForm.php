<?php

namespace App\Filament\Resources\Books\Schemas;

use App\Models\Book;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class BookForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'default' => 1,
                    'lg' => 3,
                ])
                    ->schema([
                        Section::make('Book Cover')
                            ->schema([
                                View::make('filament.resources.books.cover-upload')
                                    ->viewData(fn (Get $get, ?Book $record): array => [
                                        'coverPath' => $get('cover_image_path') ?: $record?->coverImagePath(),
                                    ]),
                                FileUpload::make('cover_image_path')
                                    ->label('Upload or replace cover')
                                    ->image()
                                    ->disk('public')
                                    ->directory(fn ($record): string => 'book-covers/' . ($record?->public_id ?: 'new'))
                                    ->visibility('public')
                                    ->imageEditor()
                                    ->imagePreviewHeight('340')
                                    ->panelAspectRatio('3:4')
                                    ->columnSpanFull()
                                    ->visible(fn ($operation): bool => $operation === 'edit')
                                    ->helperText('The existing capture cover remains visible above until a new image is saved.'),
                            ])
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 1,
                            ]),
                        Section::make('Bibliographic metadata')
                            ->schema([
                                TextInput::make('title')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                TextInput::make('subtitle')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                Textarea::make('authors_display')
                                    ->label('Authors')
                                    ->rows(2)
                                    ->columnSpanFull(),
                                Select::make('author_name_style')
                                    ->label('Author Name Style')
                                    ->options([
                                        'local' => 'Local author (first name)',
                                        'foreign' => 'Foreign author (last name)',
                                    ])
                                    ->default('local')
                                    ->required(),
                                TextInput::make('isbn13')
                                    ->label('ISBN-13')
                                    ->maxLength(13),
                                TextInput::make('publisher')
                                    ->maxLength(255),
                                TextInput::make('page_count')
                                    ->numeric()
                                    ->minValue(1),
                                Select::make('category_id')
                                    ->relationship('category', 'label')
                                    ->searchable()
                                    ->preload(),
                                Textarea::make('synopsis')
                                    ->rows(5)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 2,
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
