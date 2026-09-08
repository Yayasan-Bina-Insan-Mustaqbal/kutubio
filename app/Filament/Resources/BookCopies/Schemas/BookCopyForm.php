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
                        Select::make('funding_source')
                            ->label('Funding Source')
                            ->options([
                                'self' => 'Self-Fund',
                                'BOSP' => 'BOSP (Gov-Fund)',
                            ])
                            ->default('self')
                            ->required(),
                        Select::make('purchase_year')
                            ->label('Year of Purchase')
                            ->options([
                                'Old Collection' => 'Old Collection',
                                ...collect(range(2023, 2030))->mapWithKeys(fn (int $year): array => [(string) $year => (string) $year])->all(),
                            ])
                            ->default('Old Collection')
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
