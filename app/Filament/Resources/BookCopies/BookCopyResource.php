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
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
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
                        $pdf = $printService->generateStickerSheet(collect([$record]), $profile, (int)$data['skip_slots']);
                        return response()->streamDownload(
                            fn () => print($pdf),
                            "sticker-{$record->public_id}.pdf"
                        );
                    }),
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
                        $pdf = $printService->generateStickerSheet($records, $profile, (int)$data['skip_slots']);
                        return response()->streamDownload(
                            fn () => print($pdf),
                            "stickers-" . now()->format('Y-m-d') . ".pdf"
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
