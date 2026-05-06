<?php

namespace App\Filament\Resources\Books\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BookInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Book')
                    ->schema([
                        TextEntry::make('public_id')
                            ->label('Public ID')
                            ->copyable(),
                        TextEntry::make('title'),
                        TextEntry::make('subtitle')
                            ->placeholder('None'),
                        TextEntry::make('authors_display')
                            ->label('Authors')
                            ->placeholder('None'),
                        TextEntry::make('isbn13')
                            ->label('ISBN-13')
                            ->placeholder('None'),
                        TextEntry::make('publisher')
                            ->placeholder('None'),
                        TextEntry::make('page_count')
                            ->numeric()
                            ->placeholder('None'),
                        TextEntry::make('category.label')
                            ->placeholder('Uncategorized'),
                        TextEntry::make('synopsis')
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
