<?php

namespace App\Filament\Resources\BookCopies\Schemas;

use App\Enums\BookCopyStatus;
use App\Models\BookCopy;
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
                        TextInput::make('public_id')
                            ->label('Public ID')
                            ->disabled()
                            ->dehydrated(false),
                        Select::make('book_id')
                            ->relationship('book', 'title')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('funding_source')
                            ->label('Funding Source')
                            ->options([
                                'self' => 'Self-Fund',
                                'BOSP' => 'BOSP (Gov-Fund)',
                            ])
                            ->placeholder('Select funding source')
                            ->formatStateUsing(fn (?string $state): ?string => BookCopy::normalizeStoredValue('funding_source', $state)),
                        Select::make('purchase_year')
                            ->label('Year of Purchase')
                            ->options([
                                'Old Collection' => 'Old Collection',
                            ] + collect(range(2023, 2030))->mapWithKeys(fn (int $year): array => [(string) $year => (string) $year])->all())
                            ->default(fn (?BookCopy $record): ?string => $record?->getRawOriginal('purchase_year') ?? 'Old Collection')
                            ->placeholder('Select purchase year'),
                        Select::make('status')
                            ->options(fn (?BookCopy $record): array => collect(BookCopyStatus::cases())
                                ->reject(fn (BookCopyStatus $status): bool => $status === BookCopyStatus::Borrowed && $record?->status !== BookCopyStatus::Borrowed)
                                ->mapWithKeys(fn (BookCopyStatus $status): array => [$status->value => $status->getLabel() ?? $status->value])
                                ->all())
                            ->disabled(fn (?BookCopy $record): bool => $record?->status === BookCopyStatus::Borrowed)
                            ->dehydrated(fn (?BookCopy $record): bool => $record?->status !== BookCopyStatus::Borrowed)
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
