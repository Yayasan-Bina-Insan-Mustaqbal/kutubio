<?php

namespace App\Filament\Widgets;

use App\Enums\LoanStatus;
use App\Models\Loan;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use App\Services\PrintService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;

class LoanRecap extends TableWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    public function getHeader(): ?View
    {
        return view('filament.widgets.clipboard-handler');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Loan::query()
                    ->whereIn('status', [LoanStatus::Active, 'borrowed'])
                    ->with(['borrower', 'bookCopy.book'])
                    ->orderBy('due_at', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('borrower.name')
                    ->label('Borrower')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('borrower.class')
                    ->label('Class')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bookCopy.book.title')
                    ->label('Book Title')
                    ->limit(40)
                    ->searchable(),
                Tables\Columns\TextColumn::make('due_at')
                    ->label('Due Date')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->color(fn (Loan $record): string => $record->due_at->isPast() ? 'danger' : 'warning')
                    ->description(fn (Loan $record): string => $record->due_at->diffForHumans()),
            ])
            ->filters([
                Tables\Filters\Filter::make('overdue')
                    ->query(fn (Builder $query): Builder => $query->where('due_at', '<', now())),
            ])
            ->actions([
                Action::make('copyReminder')
                    ->label('Copy')
                    ->icon('heroicon-m-clipboard')
                    ->color('success')
                    ->extraAttributes(fn (?Loan $record): array => [
                        'onclick' => $record ? "
                            const text = this.getAttribute('data-reminder');
                            navigator.clipboard.writeText(text).then(() => {
                                new FilamentNotification()
                                    .title('Reminder copied to clipboard')
                                    .success()
                                    .send();
                            });
                        " : "",
                        'data-reminder' => $record ? "Assalamu'alaikum, this is a reminder for {$record->borrower->name} " .
                                           ($record->borrower->class ? "({$record->borrower->class}) " : "") .
                                           "to return the book \"{$record->bookCopy->book->title}\" which " .
                                           ($record->due_at->isPast() ? "was due on " : "is due on ") .
                                           $record->due_at->format('d M Y') . ". Thank you." : "",
                    ]),
            ])
            ->bulkActions([
                BulkAction::make('printBorrowerReport')
                    ->label('Print Borrower Report')
                    ->icon('heroicon-o-printer')
                    ->action(function (Collection $records, PrintService $printService) {
                        $pdf = $printService->generateLoanList($records);
                        $filename = Str::uuid()->toString().'.pdf';
                        $originalName = 'borrowers-not-returned-'.now()->format('Y-m-d-His').'.pdf';
                        Storage::disk('local')->put('temp-pdfs/'.$filename, $pdf);

                        return redirect()->away(
                            URL::signedRoute('download.temp', ['filename' => $filename, 'name' => $originalName])
                        );
                    }),
            ]);
    }
}
