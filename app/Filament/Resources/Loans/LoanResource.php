<?php

namespace App\Filament\Resources\Loans;

use App\Enums\BookCopyStatus;
use App\Enums\LoanStatus;
use App\Filament\Resources\Loans\Pages\CreateLoan;
use App\Filament\Resources\Loans\Pages\EditLoan;
use App\Filament\Resources\Loans\Pages\ListLoans;
use App\Filament\Resources\Loans\Pages\ViewLoan;
use App\Models\BookCopy;
use App\Models\Loan;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
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
                    ->disableOptionsWhenSelectedInRedundantRelationships()
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
                    ->searchable()
                    ->sortable(),
                TextColumn::make('bookCopy.book.title')
                    ->label('Book')
                    ->searchable()
                    ->sortable(),
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
                    ->badge(),
            ])
            ->filters([
                \Filament\Tables\Filters\TrashedFilter::make()
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
                \Filament\Tables\Actions\Action::make('flag_for_deletion')
                    ->label('Flag for Deletion')
                    ->icon('heroicon-o-flag')
                    ->color('danger')
                    ->hidden(fn () => auth()->user()->isAdmin())
                    ->form([
                        \Filament\Forms\Components\Textarea::make('deletion_reason')
                            ->label('Reason for deletion')
                            ->required(),
                    ])
                    ->action(function (Loan $record, array $data) {
                        $record->update(['deletion_reason' => $data['deletion_reason']]);
                        $record->delete();
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Loan record flagged for deletion')
                            ->success()
                            ->send();
                    }),

                \Filament\Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()->isAdmin()),
                
                \Filament\Tables\Actions\RestoreAction::make()
                    ->visible(fn () => auth()->user()->isAdmin()),
                
                \Filament\Tables\Actions\ForceDeleteAction::make()
                    ->visible(fn () => auth()->user()->isAdmin()),
                
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
