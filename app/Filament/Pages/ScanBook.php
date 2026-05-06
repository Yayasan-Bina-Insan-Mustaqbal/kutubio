<?php

namespace App\Filament\Pages;

use App\Enums\BookCopyStatus;
use App\Enums\LoanStatus;
use App\Models\BookCopy;
use App\Models\Borrower;
use App\Models\Loan;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\On;
use UnitEnum;

class ScanBook extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-camera';

    protected string $view = 'filament.pages.scan-book';

    protected static ?string $navigationLabel = 'Capture Book';

    public static ?string $title = 'Capture Book';

    protected static string|UnitEnum|null $navigationGroup = 'Circulation';

    protected static ?int $navigationSort = 1;

    public ?string $scannedQrCode = null;

    public ?BookCopy $bookCopy = null;

    public ?Loan $currentLoan = null;

    public ?string $processMode = null; // 'loan', 'return', 'not_found'

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('borrower_id')
                    ->label('Search Borrower')
                    ->placeholder('Enter name or identifier...')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => Borrower::query()
                        ->where('name', 'ilike', "%{$search}%")
                        ->orWhere('identifier', 'ilike', "%{$search}%")
                        ->limit(20)
                        ->get()
                        ->mapWithKeys(fn (Borrower $borrower) => [
                            $borrower->id => "{$borrower->name}" . ($borrower->class ? " ({$borrower->class})" : "") . " [{$borrower->identifier}]"
                        ])
                        ->toArray()
                    )
                    ->getOptionLabelUsing(fn ($value): ?string => Borrower::find($value)?->name)
                    ->required()
                    ->live(),
            ])
            ->statePath('data');
    }

    #[On('qr-scanned')]
    public function handleQrScanned(string $value): void
    {
        \Illuminate\Support\Facades\Log::info('QR Scanned on server:', ['value' => $value]);

        if ($this->scannedQrCode === $value && $this->bookCopy) {
            $this->dispatch('scanner-reset');
            return;
        }

        $this->scannedQrCode = $value;
        $this->bookCopy = BookCopy::with(['book', 'book.category'])->where('qr_payload', $value)->first();

        if (! $this->bookCopy) {
            $this->processMode = 'not_found';
            $this->currentLoan = null;
            
            Notification::make()
                ->title('Book Not Found')
                ->body("No book matches QR: {$value}")
                ->danger()
                ->send();
            
            $this->dispatch('scanner-reset');
            return;
        }

        $this->currentLoan = Loan::with('borrower')
            ->where('book_copy_id', $this->bookCopy->id)
            ->where('status', LoanStatus::Active)
            ->first();

        if ($this->currentLoan) {
            $this->processMode = 'return';
        } else {
            $this->processMode = 'loan';
            $this->form->fill();
        }
    }

    public function processLoan(): void
    {
        $this->validate();

        DB::transaction(function () {
            Loan::create([
                'book_copy_id' => $this->bookCopy->id,
                'borrower_id' => $this->data['borrower_id'],
                'loaned_at' => now(),
                'due_at' => now()->addDays(14),
                'status' => LoanStatus::Active,
            ]);

            $this->bookCopy->update([
                'status' => BookCopyStatus::Borrowed,
            ]);
        });

        Notification::make()
            ->title('Loan Processed')
            ->success()
            ->send();

        $this->resetScanner();
    }

    public function processReturn(): void
    {
        if (! $this->currentLoan) return;

        DB::transaction(function () {
            $this->currentLoan->update([
                'returned_at' => now(),
                'status' => LoanStatus::Returned,
            ]);

            $this->bookCopy->update([
                'status' => BookCopyStatus::Available,
            ]);
        });

        Notification::make()
            ->title('Book Returned')
            ->success()
            ->send();

        $this->resetScanner();
    }

    public function resetScanner(): void
    {
        $this->scannedQrCode = null;
        $this->bookCopy = null;
        $this->currentLoan = null;
        $this->processMode = null;
        $this->form->fill();
        
        $this->dispatch('scanner-reset');
    }
}
