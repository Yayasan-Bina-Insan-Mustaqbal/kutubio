<?php

namespace App\Filament\Resources\BookCopies\Schemas;

use App\Enums\BookCopyStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BookCopyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Physical copy')
                    ->schema([
                        Select::make('book_id')
                            ->relationship('book', 'title')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('status')
                            ->options(BookCopyStatus::class)
                            ->required(),
                        TextInput::make('tracking_code')
                            ->maxLength(255),
                        TextInput::make('qr_payload')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Generated automatically from the copy public ID.'),
                        DatePicker::make('acquired_at'),
                        Textarea::make('location_note')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
