<?php

namespace App\Filament\Resources\Books\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BookForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                    ->columns(2),
            ]);
    }
}
