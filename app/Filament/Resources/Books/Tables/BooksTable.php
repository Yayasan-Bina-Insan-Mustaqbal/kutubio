<?php

namespace App\Filament\Resources\Books\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BooksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('authors_display')
                    ->label('Authors')
                    ->searchable()
                    ->limit(40)
                    ->placeholder('None'),
                TextColumn::make('isbn13')
                    ->label('ISBN-13')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('category.label')
                    ->sortable()
                    ->placeholder('Uncategorized'),
                TextColumn::make('copies_count')
                    ->label('Copies')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('metadata_revisions_count')
                    ->label('Revisions')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->relationship('category', 'label')
                    ->searchable()
                    ->preload(),
                \Filament\Tables\Filters\TrashedFilter::make()
                    ->visible(fn () => auth()->user()->isAdmin()),
            ]);
    }
}
