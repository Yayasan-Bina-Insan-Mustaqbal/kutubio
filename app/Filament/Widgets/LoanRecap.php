<?php

namespace App\Filament\Widgets;

use App\Enums\LoanStatus;
use App\Models\Loan;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
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
                    ->where('status', LoanStatus::Active)
                    ->where('due_at', '<=', now()->addDays(2))
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
                    ->extraAttributes([
                        'onclick' => "
                            const text = this.getAttribute('data-reminder');
                            navigator.clipboard.writeText(text).then(() => {
                                new FilamentNotification()
                                    .title('Reminder copied to clipboard')
                                    .success()
                                    .send();
                            });
                        ",
                    ])
                    ->evaluate(fn (Loan $record) => [
                        'data-reminder' => "Assalamu'alaikum, mengingatkan kepada {$record->borrower->name} " . 
                                           ($record->borrower->class ? "({$record->borrower->class}) " : "") . 
                                           "untuk mengembalikan buku \"{$record->bookCopy->book->title}\" yang " . 
                                           ($record->due_at->isPast() ? "sudah jatuh tempo pada " : "akan jatuh tempo pada ") . 
                                           $record->due_at->format('d M Y') . ". Syukran.",
                    ]),
            ])
            ->bulkActions([
                BulkAction::make('copyBulkReminder')
                    ->label('Copy Bulk Reminder')
                    ->icon('heroicon-m-clipboard-document-list')
                    ->action(function (Collection $records) {
                        $text = "DAFTAR REMINDER PENGEMBALIAN BUKU:\n\n";
                        foreach ($records as $record) {
                            $status = $record->due_at->isPast() ? "DUE" : "SOON";
                            $text .= "- {$record->borrower->name} (" . ($record->borrower->class ?? '-') . "): {$record->bookCopy->book->title} [{$status}: {$record->due_at->format('d M Y')}]\n";
                        }
                        
                        $this->dispatch('copy-to-clipboard', text: $text);
                    }),
            ]);
    }
}
