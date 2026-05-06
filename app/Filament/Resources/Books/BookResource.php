<?php

namespace App\Filament\Resources\Books;

use App\Filament\Resources\Books\Pages\CreateBook;
use App\Filament\Resources\Books\Pages\EditBook;
use App\Filament\Resources\Books\Pages\ListBooks;
use App\Filament\Resources\Books\Pages\ViewBook;
use App\Filament\Resources\Books\Schemas\BookForm;
use App\Filament\Resources\Books\Schemas\BookInfolist;
use App\Filament\Resources\Books\Tables\BooksTable;
use App\Models\Book;
use App\Jobs\FetchBookMetadataJob;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use UnitEnum;

class BookResource extends Resource
{
    protected static ?string $model = Book::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Library';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return BookForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BookInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BooksTable::configure($table)
            ->actions([
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

                Action::make('flag_for_deletion')
                    ->label('Flag for Deletion')
                    ->icon('heroicon-o-flag')
                    ->iconButton()
                    ->color('danger')
                    ->hidden(fn () => auth()->user()->isAdmin())
                    ->form([
                        Textarea::make('deletion_reason')
                            ->label('Reason for deletion')
                            ->required(),
                    ])
                    ->action(function (Book $record, array $data) {
                        $record->update(['deletion_reason' => $data['deletion_reason']]);
                        $record->delete();

                        Notification::make()
                            ->title('Book flagged for deletion')
                            ->success()
                            ->send();
                    }),

                DeleteAction::make()->iconButton()->hidden(),
                RestoreAction::make()->iconButton()->hidden(),
                ForceDeleteAction::make()->iconButton()->hidden(),
            ])
            ->bulkActions([
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBooks::route('/'),
            'create' => CreateBook::route('/create'),
            'view' => ViewBook::route('/{record}'),
            'edit' => EditBook::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('category')
            ->withCount(['copies', 'metadataRevisions']);
    }
}
