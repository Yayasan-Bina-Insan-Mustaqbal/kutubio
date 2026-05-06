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
use App\Services\PrintService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
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
                Action::make('print_card')
                    ->label('Print Card')
                    ->icon('heroicon-o-printer')
                    ->action(function (Book $record, PrintService $printService) {
                        $pdf = $printService->generateBookCards(collect([$record]));

                        $filename = Str::uuid()->toString().'.pdf';
                        $originalName = "book-card-{$record->public_id}.pdf";
                        Storage::disk('local')->put('temp-pdfs/'.$filename, $pdf);

                        return redirect()->away(
                            URL::signedRoute('download.temp', ['filename' => $filename, 'name' => $originalName])
                        );
                    }),

                Action::make('flag_for_deletion')
                    ->label('Flag for Deletion')
                    ->icon('heroicon-o-flag')
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

                DeleteAction::make()
                    ->visible(fn () => auth()->user()->isAdmin()),

                RestoreAction::make()
                    ->visible(fn () => auth()->user()->isAdmin()),

                ForceDeleteAction::make()
                    ->visible(fn () => auth()->user()->isAdmin()),
            ])
            ->bulkActions([
                BulkAction::make('print_cards')
                    ->label('Print Cards')
                    ->icon('heroicon-o-printer')
                    ->action(function (Collection $records, PrintService $printService) {
                        $pdf = $printService->generateBookCards($records);

                        $filename = Str::uuid()->toString().'.pdf';
                        $originalName = 'book-cards-'.now()->format('Y-m-d').'.pdf';
                        Storage::disk('local')->put('temp-pdfs/'.$filename, $pdf);

                        return redirect()->away(
                            URL::signedRoute('download.temp', ['filename' => $filename, 'name' => $originalName])
                        );
                    }),
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
