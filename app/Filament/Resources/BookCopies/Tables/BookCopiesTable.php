<?php

namespace App\Filament\Resources\BookCopies\Tables;

use App\Enums\BookCopyStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BookCopiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('public_id')
                    ->label('Public ID')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('book.title')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('tracking_code')
                    ->searchable()
                    ->placeholder('None'),
                TextColumn::make('qr_payload')
                    ->searchable()
                    ->copyable()
                    ->limit(36),
                TextColumn::make('acquired_at')
                    ->date()
                    ->sortable()
                    ->placeholder('None'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(BookCopyStatus::class),
                \Filament\Tables\Filters\TrashedFilter::make()
                    ->visible(fn () => auth()->user()->isAdmin()),
            ])
            ->recordActions([
                ViewAction::make()->iconButton(),
                EditAction::make()->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
