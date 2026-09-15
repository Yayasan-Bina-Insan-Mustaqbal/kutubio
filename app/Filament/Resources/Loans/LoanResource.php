<?php

namespace App\Filament\Resources\Loans;

use App\Enums\BookCopyStatus;
use App\Enums\BorrowerType;
use App\Enums\LoanStatus;
use App\Filament\Resources\Loans\Pages\CreateLoan;
use App\Filament\Resources\Loans\Pages\EditLoan;
use App\Filament\Resources\Loans\Pages\ListLoans;
use App\Filament\Resources\Loans\Pages\ViewLoan;
use App\Models\BookCopy;
use App\Models\Borrower;
use App\Models\Loan;
use App\Services\PrintService;
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
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use UnitEnum;

class LoanResource extends Resource
{
    protected static ?string $model = Loan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|UnitEnum|null $navigationGroup = 'Circulation';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('borrower_id')
                    ->relationship('borrower', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('book_copy_id')
                    ->label('Book Copy')
                    ->relationship('bookCopy', 'public_id')
                    ->getOptionLabelFromRecordUsing(fn (BookCopy $record) => "{$record->book->title} ({$record->public_id})")
                    ->searchable()
                    ->preload()
                    ->required()
                    // Only show available copies when creating
                    ->options(function (string $context) {
                        if ($context === 'create') {
                            return BookCopy::where('status', BookCopyStatus::Available)->get()->mapWithKeys(function ($copy) {
                                return [$copy->id => "{$copy->book->title} ({$copy->public_id})"];
                            });
                        }

                        return BookCopy::all()->mapWithKeys(function ($copy) {
                            return [$copy->id => "{$copy->book->title} ({$copy->public_id})"];
                        });
                    }),
                DateTimePicker::make('loaned_at')
                    ->default(now())
                    ->required(),
                DateTimePicker::make('due_at')
                    ->default(now()->addWeeks(2))
                    ->required(),
                DateTimePicker::make('returned_at'),
                Select::make('status')
                    ->options(LoanStatus::class)
                    ->required()
                    ->default(LoanStatus::Active),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('borrower.name')
                    ->label('Borrower')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('bookCopy.book.title')
                    ->label('Book Title')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('borrower.class')
                    ->label('Class')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('bookCopy.public_id')
                    ->label('Copy ID')
                    ->searchable(),
                TextColumn::make('loaned_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('due_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(LoanStatus::class),
                Filter::make('borrower_profile')
                    ->label('Borrower Profile')
                    ->form([
                        Select::make('type')
                            ->label('Type')
                            ->options(fn (): array => [
                                'all' => 'All types',
                                ...collect(BorrowerType::cases())
                                    ->mapWithKeys(fn (BorrowerType $type): array => [$type->value => $type->getLabel()])
                                    ->all(),
                            ])
                            ->default('all')
                            ->live()
                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                if ($state !== null && $state !== 'all' && $state !== BorrowerType::Student->value) {
                                    $set('class', 'all');
                                }
                            }),
                        Select::make('class')
                            ->label('Class')
                            ->options(function (Get $get): array {
                                $type = $get('type');

                                if ($type === null || $type === 'all' || $type !== BorrowerType::Student->value) {
                                    return ['all' => 'All classes'];
                                }

                                return Borrower::query()
                                    ->where('type', BorrowerType::Student)
                                    ->whereNotNull('class')
                                    ->distinct()
                                    ->orderBy('class')
                                    ->pluck('class', 'class')
                                    ->mapWithKeys(fn (string $class): array => [$class => $class])
                                    ->prepend('All classes', 'all')
                                    ->all();
                            })
                            ->default('all')
                            ->live()
                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                if ($state !== null && $state !== 'all') {
                                    $set('type', BorrowerType::Student->value);
                                }
                            }),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $type = $data['type'] ?? 'all';
                        $class = $data['class'] ?? 'all';

                        if ($type !== 'all' && $type !== null && $type !== '') {
                            $query->whereRelation('borrower', 'type', $type);
                        }

                        if (($type === 'all' || $type === BorrowerType::Student->value) && $class !== 'all' && $class !== null && $class !== '') {
                            $query->whereRelation('borrower', 'class', $class)
                                ->whereRelation('borrower', 'type', BorrowerType::Student->value);
                        }

                        return $query;
                    }),
                TrashedFilter::make()
                    ->visible(fn () => auth()->user()->isAdmin()),
            ])
            ->actions([
                Action::make('return')
                    ->label('Return')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (Loan $record) => $record->status === LoanStatus::Active)
                    ->action(function (Loan $record) {
                        $record->update([
                            'returned_at' => now(),
                            'status' => LoanStatus::Returned,
                        ]);

                        $record->bookCopy->update([
                            'status' => BookCopyStatus::Available,
                        ]);

                        Notification::make()
                            ->title('Book returned successfully')
                            ->success()
                            ->send();
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
                    ->action(function (Loan $record, array $data) {
                        $record->update(['deletion_reason' => $data['deletion_reason']]);
                        $record->delete();

                        Notification::make()
                            ->title('Loan record flagged for deletion')
                            ->success()
                            ->send();
                    }),

                DeleteAction::make()
                    ->visible(fn () => auth()->user()->isAdmin()),

                RestoreAction::make()
                    ->visible(fn () => auth()->user()->isAdmin()),

                ForceDeleteAction::make()
                    ->visible(fn () => auth()->user()->isAdmin()),

                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('print_selected')
                        ->label('Print Selected')
                        ->icon('heroicon-o-printer')
                        ->action(function (Collection|EloquentCollection $records, PrintService $printService) {
                            $records = $records->filter(fn (Loan $record): bool => $record instanceof Loan);

                            if ($records->isEmpty()) {
                                Notification::make()
                                    ->title('No loan records selected')
                                    ->warning()
                                    ->send();

                                return;
                            }

                            $pdf = $printService->generateLoanList($records);
                            $filename = Str::uuid()->toString().'.pdf';
                            $originalName = 'loan-report-'.now()->format('Y-m-d-His').'.pdf';
                            Storage::disk('local')->put('temp-pdfs/'.$filename, $pdf);

                            return redirect()->away(
                                URL::signedRoute('download.temp', ['filename' => $filename, 'name' => $originalName])
                            );
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLoans::route('/'),
            'create' => CreateLoan::route('/create'),
            'view' => ViewLoan::route('/{record}'),
            'edit' => EditLoan::route('/{record}/edit'),
        ];
    }
}
