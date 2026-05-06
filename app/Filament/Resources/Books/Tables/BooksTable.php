<?php

namespace App\Filament\Resources\Books\Tables;

use App\Jobs\FetchBookMetadataJob;
use App\Models\Book;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

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
            ])
            ->recordActions([
                ViewAction::make()->iconButton(),
                EditAction::make()->iconButton(),
                Action::make('acquireMetadata')
                    ->label('Acquire Metadata')
                    ->icon('heroicon-m-sparkles')
                    ->iconButton()
                    ->color('primary')
                    ->form([
                        Select::make('provider')
                            ->label('Metadata Provider')
                            ->options([
                                'open_library' => 'Open Library (ISBN)',
                                'isbn_search' => 'ISBN Search (HTML Scraper)',
                            ])
                            ->default('open_library')
                            ->required(),
                    ])
                    ->action(function (Book $record, array $data) {
                        if (empty($record->isbn13)) {
                            Notification::make()
                                ->title('Metadata Acquisition Failed')
                                ->body('Book has no ISBN-13.')
                                ->danger()
                                ->send();
                            return;
                        }

                        FetchBookMetadataJob::dispatch($record, $data['provider']);

                        Notification::make()
                            ->title('Metadata acquisition queued')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('acquireMetadataBulk')
                        ->label('Acquire Metadata')
                        ->icon('heroicon-m-sparkles')
                        ->form([
                            Select::make('provider')
                                ->label('Metadata Provider')
                                ->options([
                                    'open_library' => 'Open Library (ISBN)',
                                    'isbn_search' => 'ISBN Search (HTML Scraper)',
                                ])
                                ->default('open_library')
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $count = 0;
                            foreach ($records as $record) {
                                if (!empty($record->isbn13)) {
                                    FetchBookMetadataJob::dispatch($record, $data['provider']);
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->title("Queued metadata acquisition for {$count} books")
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }
}
