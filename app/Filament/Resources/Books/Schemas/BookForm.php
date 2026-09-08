<?php

namespace App\Filament\Resources\Books\Schemas;

use App\Enums\BookCopyStatus;
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

                Section::make('Inventory Management')
                    ->schema([
                        TextInput::make('current_copies_count')
                            ->label('Current Copies')
                            ->placeholder(fn ($record) => $record?->copies()->count() ?? 0)
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('add_copies')
                            ->label('Add New Copies')
                            ->helperText('Enter the number of additional copies to create. This will not delete existing ones.')
                            ->numeric()
                            ->default(0)
                            ->dehydrated(false)
                            ->visible(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\EditRecord),
                        Select::make('add_copies_status')
                            ->label('Copies Status')
                            ->options(BookCopyStatus::class)
                            ->default(BookCopyStatus::Draft)
                            ->dehydrated(false)
                            ->visible(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\EditRecord),
                        Select::make('add_copies_funding_source')
                            ->label('Copies Funding Source')
                            ->options([
                                'self' => 'Self-Fund',
                                'BOSP' => 'BOSP (Gov-Fund)',
                            ])
                            ->default('self')
                            ->dehydrated(false)
                            ->visible(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\EditRecord),
                        Select::make('add_copies_purchase_year')
                            ->label('Copies Year of Purchase')
                            ->options([
                                'Old Collection' => 'Old Collection',
                            ] + collect(range(2023, 2030))->mapWithKeys(fn (int $year): array => [(string) $year => (string) $year])->all())
                            ->default('Old Collection')
                            ->dehydrated(false)
                            ->visible(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\EditRecord),
                    ])
                    ->columns(2),
            ]);
    }
}
