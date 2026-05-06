<?php

namespace App\Filament\Resources\BookCopies;

use App\Filament\Resources\BookCopies\Pages\CreateBookCopy;
use App\Filament\Resources\BookCopies\Pages\EditBookCopy;
use App\Filament\Resources\BookCopies\Pages\ListBookCopies;
use App\Filament\Resources\BookCopies\Pages\ViewBookCopy;
use App\Filament\Resources\BookCopies\Schemas\BookCopyForm;
use App\Filament\Resources\BookCopies\Schemas\BookCopyInfolist;
use App\Filament\Resources\BookCopies\Tables\BookCopiesTable;
use App\Models\BookCopy;
use App\Models\PrintProfile;
use App\Services\PrintService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
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

class BookCopyResource extends Resource
{
    protected static ?string $model = BookCopy::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookmarkSquare;

    protected static string|UnitEnum|null $navigationGroup = 'Library';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'public_id';

    public static function form(Schema $schema): Schema
    {
        return BookCopyForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BookCopyInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BookCopiesTable::configure($table)
            ->actions([
                Action::make('print_sticker')
                    ->label('Print Sticker')
                    ->icon('heroicon-o-printer')
                    ->iconButton()
                    ->form([
                        Select::make('profile_id')
                            ->label('Print Profile')
                            ->options(PrintProfile::pluck('name', 'id'))
                            ->default(fn () => PrintProfile::where('is_default', true)->first()?->id ?? PrintProfile::first()?->id)
                            ->required(),
                        TextInput::make('skip_slots')
                            ->label('Starting Slot (Skip N)')
                            ->numeric()
                            ->default(0)
                            ->required(),
                    ])
                    ->action(function (BookCopy $record, array $data, PrintService $printService) {
                        $profile = PrintProfile::findOrFail($data['profile_id']);
                        $pdf = $printService->generateStickerSheet(collect([$record]), $profile, (int) $data['skip_slots']);

                        $filename = Str::uuid()->toString().'.pdf';
                        $originalName = "sticker-{$record->public_id}.pdf";
                        Storage::disk('local')->put('temp-pdfs/'.$filename, $pdf);

                        return redirect()->away(
                            URL::signedRoute('download.temp', ['filename' => $filename, 'name' => $originalName])
                        );
                    }),

                Action::make('print_card')
                    ->label('Print Card')
                    ->icon('heroicon-o-identification')
                    ->iconButton()
                    ->action(function (BookCopy $record, PrintService $printService) {
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
                    ->iconButton()
                    ->color('danger')
                    ->hidden(fn () => auth()->user()->isAdmin())
                    ->form([
                        Textarea::make('deletion_reason')
                            ->label('Reason for deletion')
                            ->required(),
                    ])
                    ->action(function (BookCopy $record, array $data) {
                        $record->update(['deletion_reason' => $data['deletion_reason']]);
                        $record->delete();

                        Notification::make()
                            ->title('Book copy flagged for deletion')
                            ->success()
                            ->send();
                    }),

                DeleteAction::make()->iconButton()->hidden(),
                RestoreAction::make()->iconButton()->hidden(),
                ForceDeleteAction::make()->iconButton()->hidden(),
            ])
            ->bulkActions([
                BulkAction::make('print_stickers')
                    ->label('Print Stickers')
                    ->icon('heroicon-o-printer')
                    ->form([
                        Select::make('profile_id')
                            ->label('Print Profile')
                            ->options(PrintProfile::pluck('name', 'id'))
                            ->default(fn () => PrintProfile::where('is_default', true)->first()?->id ?? PrintProfile::first()?->id)
                            ->required(),
                        TextInput::make('skip_slots')
                            ->label('Starting Slot (Skip N)')
                            ->numeric()
                            ->default(0)
                            ->required(),
                    ])
                    ->action(function (Collection $records, array $data, PrintService $printService) {
                        $profile = PrintProfile::findOrFail($data['profile_id']);
                        $pdf = $printService->generateStickerSheet($records, $profile, (int) $data['skip_slots']);

                        $filename = Str::uuid()->toString().'.pdf';
                        $originalName = 'stickers-'.now()->format('Y-m-d').'.pdf';
                        Storage::disk('local')->put('temp-pdfs/'.$filename, $pdf);

                        return redirect()->away(
                            URL::signedRoute('download.temp', ['filename' => $filename, 'name' => $originalName])
                        );
                    }),

                BulkAction::make('print_cards')
                    ->label('Print Cards')
                    ->icon('heroicon-o-identification')
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
            'index' => ListBookCopies::route('/'),
            'create' => CreateBookCopy::route('/create'),
            'view' => ViewBookCopy::route('/{record}'),
            'edit' => EditBookCopy::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('book');
    }
}
