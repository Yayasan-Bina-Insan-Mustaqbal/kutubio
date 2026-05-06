<?php

namespace App\Filament\Resources\BookCopies\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BookCopyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Copy')
                    ->schema([
                        TextEntry::make('public_id')
                            ->label('Public ID')
                            ->copyable(),
                        TextEntry::make('book.title'),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('tracking_code')
                            ->placeholder('None'),
                        TextEntry::make('qr_payload')
                            ->copyable(),
                        TextEntry::make('acquired_at')
                            ->date()
                            ->placeholder('None'),
                        TextEntry::make('location_note')
                            ->placeholder('None')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Deletion Information')
                    ->visible(fn ($record) => $record?->trashed())
                    ->schema([
                        TextEntry::make('deletion_reason')
                            ->label('Reason for deletion')
                            ->columnSpanFull()
                            ->color('danger')
                            ->weight('bold'),
                    ]),
            ]);
    }
}
