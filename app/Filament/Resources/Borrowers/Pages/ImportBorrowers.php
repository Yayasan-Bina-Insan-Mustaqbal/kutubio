<?php

namespace App\Filament\Resources\Borrowers\Pages;

use App\Filament\Resources\Borrowers\BorrowerResource;
use App\Models\Borrower;
use App\Models\BorrowerImport;
use App\Models\BorrowerImportRow;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Artisan;

class ImportBorrowers extends Page
{
    public string $rowFilter = 'all';

    public ?int $selectedImportId = null;
    public function getCandidates(int $rowId): array
    {
        $row = BorrowerImportRow::find($rowId);
        if (! $row) {
            return [];
        }

        return Borrower::query()
            ->where('name', 'ilike', '%'.str_replace('%', '\\%', trim((string) ($row->raw_data['nama_siswa'] ?? ''))).'%')
            ->limit(10)
            ->get()
            ->mapWithKeys(fn (Borrower $borrower): array => [$borrower->id => "{$borrower->name} ({$borrower->identifier})"])
            ->all();
    }

    public function matchRow(int $rowId, int $borrowerId): void
    {
        BorrowerImportRow::whereKey($rowId)->update([
            'matched_borrower_id' => $borrowerId,
            'match_method' => 'manual_review',
            'confidence' => 1,
            'resolution_status' => 'matched',
            'error_message' => null,
        ]);
    }


    public function setRowFilter(string $filter): void
    {
        $this->rowFilter = $filter;
    }


    protected static string $resource = BorrowerResource::class;

    protected string $view = 'filament.resources.borrowers.pages.import-borrowers';

    public function selectImport(int $importId): void
    {
        $this->selectedImportId = $importId;
        $this->rowFilter = 'all';
    }

    public function updateInvalidRow(int $rowId, string $name, string $class, string $gender, string $action): void
    {
        $row = BorrowerImportRow::findOrFail($rowId);
        $data = $row->raw_data ?? [];
        $data['nama_siswa'] = trim($name);
        $data['kelas'] = strtoupper(trim($class));
        $data['jenis_kelamin'] = trim($gender);

        if ($action === 'ignored') {
            $row->update(['raw_data' => $data, 'resolution_status' => 'ignored', 'error_message' => 'Ignored by administrator.']);
            return;
        }

        preg_match('/^(\d+)\s*[- ]?\s*([A-Za-z]*)$/', $data['kelas'], $matches);
        $isValid = $data['nama_siswa'] !== '' && isset($matches[1]);
        $row->update([
            'raw_data' => $data,
            'grade' => $isValid ? (int) $matches[1] : null,
            'section' => $isValid && ($matches[2] ?? '') !== '' ? strtoupper($matches[2]) : null,
            'resolution_status' => $isValid ? 'corrected' : 'invalid',
            'match_method' => $isValid ? 'manual_correction' : 'none',
            'confidence' => $isValid ? 1 : 0,
            'error_message' => $isValid ? null : 'Name and class are required.',
        ]);
    }

    public function getImports(): \Illuminate\Database\Eloquent\Collection
    {
        return BorrowerImport::withCount('rows')->latest()->limit(10)->get();
    }

    public function getSelectedImportRows(): \Illuminate\Database\Eloquent\Collection
    {
        $query = BorrowerImportRow::where('borrower_import_id', $this->selectedImportId)->orderBy('row_number');

        if ($this->rowFilter !== 'all') {
            $query->where('resolution_status', $this->rowFilter);
        }

        return $this->selectedImportId ? $query->get() : new \Illuminate\Database\Eloquent\Collection;
    }

    public function getSelectedImportCounts(): array
    {
        return $this->getSelectedImportRows()->groupBy('resolution_status')->map->count()->all();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('fetch')
                ->label('Fetch & Preview')
                ->schema([
                    TextInput::make('url')->label('Google Sheets URL')->url()->required(),
                    TextInput::make('academic_year')->label('Academic Year')->placeholder('YYYY, e.g. 2026')->helperText('Enter the four-digit start year; 2026 becomes 2026/2027.')->required(),
                ])
                ->action(function (array $data): void {
                    $exitCode = Artisan::call('kutubio:import-borrowers-google-sheet', ['url' => $data['url'], 'academicYear' => $data['academic_year']]);
                    Notification::make()->title($exitCode === 0 ? 'Preview ready' : 'Preview failed')->body(Artisan::output())->color($exitCode === 0 ? 'success' : 'danger')->send();
                }),
            Action::make('approve')
                ->label('Approve & Import')
                ->color('warning')
                ->schema([
                    Select::make('import_id')->options(fn (): array => BorrowerImport::where('status', 'preview')->latest()->limit(20)->get()->mapWithKeys(fn (BorrowerImport $import): array => [$import->id => "{$import->academic_year} · {$import->created_at->format('Y-m-d H:i')}"])->all())->required(),
                    TextInput::make('confirmation')->label('Type IMPORT to confirm')->required()->in(['IMPORT']),
                ])
                ->requiresConfirmation()
                ->action(function (array $data): void {
                    $import = BorrowerImport::with('rows')->findOrFail($data['import_id']);
                    if ($import->rows->contains(fn (BorrowerImportRow $row): bool => in_array($row->resolution_status, ['needs_review', 'invalid'], true))) {
                        Notification::make()->title('Import blocked')->body('Resolve needs review and invalid rows first.')->danger()->send();
                        return;
                    }
                    $result = Artisan::call('kutubio:import-borrowers-google-sheet', ['--commit' => true, '--import-id' => $import->id]);
                    $output = Artisan::output();
                    Notification::make()->title($result === 0 ? 'Import committed' : 'Import failed')->body($output)->color($result === 0 ? 'success' : 'danger')->send();
                }),
        ];
    }
}
