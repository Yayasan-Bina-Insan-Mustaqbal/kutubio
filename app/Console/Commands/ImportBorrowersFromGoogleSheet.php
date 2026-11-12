<?php

namespace App\Console\Commands;

use App\Models\Borrower;
use App\Models\BorrowerImport;
use App\Models\BorrowerImportRow;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ImportBorrowersFromGoogleSheet extends Command
{
    protected $signature = 'kutubio:import-borrowers-google-sheet {url?} {academicYear?} {--commit} {--import-id=}';

    protected $description = 'Preview or commit borrower rows from a Google Sheet CSV export';

    public function handle(): int
    {
        if ($this->option('commit') && $this->option('import-id')) {
            $import = BorrowerImport::with('rows')->findOrFail((int) $this->option('import-id'));
            return $this->commitImport($import);
        }

        $url = $this->argument('url');

        try {
            $academicYear = $this->academicYear($this->argument('academicYear'));
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $csvUrl = $this->csvUrl($url);
        $response = Http::timeout(30)->get($csvUrl);

        if ($response->failed()) {
            $this->error('Google Sheet could not be fetched: '.$response->status());
            return self::FAILURE;
        }

        $content = $response->body();
        $hash = hash('sha256', $content);
        $rows = $this->parseCsv($content);
        $headerIndex = collect($rows)->search(function (array $row): bool {
            $headers = array_map(fn (string $header): string => Str::of($header)->lower()->trim()->replace(' ', '_')->toString(), $row);
            return count(array_intersect(['no_urut', 'nama_siswa', 'kelas', 'jenis_kelamin'], $headers)) === 4;
        });

        if ($headerIndex === false) {
            $this->error('Missing required headers: no_urut, nama_siswa, kelas, jenis_kelamin');
            return self::FAILURE;
        }

        $headers = array_map(fn (string $header): string => Str::of($header)->lower()->trim()->replace(' ', '_')->toString(), $rows[$headerIndex]);
        $rows = array_slice($rows, $headerIndex + 1);

        $import = BorrowerImport::firstOrCreate(
            [
                'content_hash' => $hash,
                'academic_year' => $academicYear,
            ],
            [
                'source_url' => $url,
                'sheet_id' => $this->sheetId($url),
                'gid' => parse_url($url, PHP_URL_QUERY),
                'status' => 'preview',
                'created_by' => auth()->id(),
                'fetched_at' => Carbon::now(),
            ],
        );

        if ($import->wasRecentlyCreated === false) {
            $import->update([
                'source_url' => $url,
                'sheet_id' => $this->sheetId($url),
                'gid' => parse_url($url, PHP_URL_QUERY),
                'fetched_at' => Carbon::now(),
                'status' => 'preview',
            ]);
            $import->rows()->delete();
        }

        if ($import->rows()->exists()) {
            $this->info('Import '.$import->id.' already has a preview. Re-open it in the review page.');
            return self::SUCCESS;
        }

        $counts = ['new' => 0, 'matched' => 0, 'needs_review' => 0, 'invalid' => 0];
        $generatedIdentifiers = [];
        foreach ($rows as $index => $values) {
            $data = array_combine($headers, array_pad($values, count($headers), null));
            if ($this->isRepeatedHeader($data, $headers)) {
                continue;
            }
            $name = $this->normalizeDisplayName(trim((string) ($data['nama_siswa'] ?? '')));
            $rawClass = trim((string) ($data['kelas'] ?? ''));
            $class = strtoupper(preg_replace('/[\s-]+/', '', $rawClass));
            [$grade, $section] = $this->parseClass($class);
            $gender = $this->normalizeGender((string) ($data['jenis_kelamin'] ?? ''));
            $attendanceNumber = $this->normalizeAttendanceNumber($data['no_urut'] ?? null);
            $expectedIdentifier = $grade !== null && $section !== null && $attendanceNumber !== null
                ? $this->studentIdentifier($academicYear, $grade, $section, $attendanceNumber)
                : null;
            $normalizedData = [
                'nama_siswa' => $name,
                'kelas' => $grade !== null && $section !== null ? "{$grade}{$section}" : null,
                'jenis_kelamin' => $gender,
                'no_urut' => $attendanceNumber,
                'identifier' => $expectedIdentifier,
            ];
            $normalizedName = $this->normalizeName($name);
            $candidates = $normalizedName === ''
                ? collect()
                : Borrower::query()->get()->filter(fn (Borrower $borrower): bool => $this->normalizeName($borrower->name) === $normalizedName)->values();
            $status = $name === '' || $grade === null || $section === null || $attendanceNumber === null
                ? 'invalid'
                : ($candidates->count() === 1 ? 'matched' : ($candidates->count() === 0 ? 'new' : 'needs_review'));
            $errorMessage = $status === 'invalid' ? 'Name, class, section, or attendance number is invalid.' : null;

            if ($status === 'new' && $expectedIdentifier !== null && Borrower::query()->where('identifier', $expectedIdentifier)->exists()) {
                $status = 'needs_review';
                $errorMessage = "Generated identifier {$expectedIdentifier} already exists.";
            }

            if ($status === 'new' && $expectedIdentifier !== null && isset($generatedIdentifiers[$expectedIdentifier])) {
                $status = 'needs_review';
                $errorMessage = "Generated identifier {$expectedIdentifier} is duplicated in this sheet.";
            }

            if ($status === 'new' && $expectedIdentifier !== null) {
                $generatedIdentifiers[$expectedIdentifier] = true;
            }

            $counts[$status]++;
            BorrowerImportRow::create([
                'borrower_import_id' => $import->id,
                'row_number' => $index + 2,
                'raw_data' => $data,
                'normalized_data' => $normalizedData,
                'normalized_name' => $normalizedName,
                'grade' => $grade,
                'section' => $section,
                'matched_borrower_id' => $status === 'matched' ? $candidates->first()->id : null,
                'match_method' => $status === 'matched' ? 'normalized_name_candidate' : 'none',
                'confidence' => $status === 'matched' ? 0.7 : 0,
                'resolution_status' => $status,
                'error_message' => $errorMessage,
            ]);
        }

        if ($this->option('commit')) {
            DB::transaction(function () use ($import): void {
                $import->load('rows');
                $this->validateCommitRows($import);
                $this->commitRows($import);
                $import->update(['status' => 'committed', 'committed_at' => Carbon::now()]);
            });
        }

        $this->info(($this->option('commit') ? 'Committed' : 'Previewed').' import '.$import->id.'.');
        $this->table(['Status', 'Count'], collect($counts)->map(fn (int $count, string $status): array => [$status, $count])->values()->all());
        return self::SUCCESS;
    }

    private function commitImport(BorrowerImport $import): int
    {
        try {
            DB::transaction(function () use ($import): void {
                $this->validateCommitRows($import);
                $this->commitRows($import);
                $import->update(['status' => 'committed', 'committed_at' => Carbon::now()]);
            });
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $this->info('Committed import '.$import->id.'.');
        return self::SUCCESS;
    }

    private function validateCommitRows(BorrowerImport $import): void
    {
        $blocked = $import->rows->filter(fn (BorrowerImportRow $row): bool => in_array($row->resolution_status, ['needs_review', 'invalid'], true));

        if ($blocked->isNotEmpty()) {
            throw new RuntimeException("Import contains {$blocked->count()} unresolved rows.");
        }
    }

    private function commitRows(BorrowerImport $import): void
    {
        foreach ($import->load('rows')->rows as $row) {
            if (! in_array($row->resolution_status, ['new', 'matched', 'corrected'], true)) {
                continue;
            }
            $data = $row->raw_data;
            $attendanceNumber = $this->normalizeAttendanceNumber($data['no_urut'] ?? null);
            $identifier = $row->matched_borrower_id
                ? null
                : $this->studentIdentifier($import->academic_year, $row->grade, $row->section, $attendanceNumber);
            $borrower = $row->matched_borrower_id
                ? Borrower::findOrFail($row->matched_borrower_id)
                : Borrower::firstOrCreate(
                    ['identifier' => $identifier],
                    ['name' => trim($data['nama_siswa']), 'type' => 'student', 'status' => 'active'],
                );
            $borrower->update(['name' => trim($data['nama_siswa']), 'class' => trim($data['kelas'])]);
            $borrower->enrollments()->updateOrCreate(['academic_year' => $import->academic_year], ['grade' => $row->grade, 'section' => $row->section, 'class_name' => trim($data['kelas']), 'gender' => trim($data['jenis_kelamin'] ?? ''), 'status' => 'active', 'source_import_id' => $import->id]);
        }
    }

    private function isRepeatedHeader(array $data, array $headers): bool
    {
        $normalized = array_map(fn (?string $value): string => Str::of((string) $value)->lower()->trim()->replace(' ', '_')->toString(), $data);
        $headerValues = array_map(fn (string $header): string => Str::lower($header), $headers);

        return count(array_intersect($headerValues, $normalized)) >= 3;
    }

    private function parseCsv(string $content): array
    {
        $rows = array_map('str_getcsv', preg_split('/\r\n|\r|\n/', trim($content)));
        return array_values(array_filter($rows, fn (array $row): bool => count(array_filter($row, fn (?string $value): bool => filled($value))) > 0));
    }

    private function csvUrl(string $url): string
    {
        if (preg_match('~/spreadsheets/d/([^/]+)~', $url, $matches)) {
            $gid = preg_match('~gid=([^&#]+)~', $url, $gidMatches) ? $gidMatches[1] : '0';
            return "https://docs.google.com/spreadsheets/d/{$matches[1]}/export?format=csv&gid={$gid}";
        }
        throw new RuntimeException('Invalid Google Sheets URL.');
    }

    private function sheetId(string $url): ?string
    {
        return preg_match('~/spreadsheets/d/([^/]+)~', $url, $matches) ? $matches[1] : null;
    }

    private function normalizeDisplayName(string $name): string
    {
        return Str::of($name)
            ->squish()
            ->lower()
            ->title()
            ->toString();
    }

    private function normalizeGender(string $gender): string
    {
        $normalized = Str::lower(Str::ascii(trim($gender)));

        return match ($normalized) {
            'l', 'lk', 'laki laki', 'male', 'm' => 'Male',
            'p', 'pr', 'perempuan', 'female', 'f' => 'Female',
            default => Str::of(trim($gender))->squish()->toString(),
        };
    }

    private function normalizeAttendanceNumber(mixed $number): ?int
    {
        if (! is_numeric($number)) {
            return null;
        }

        $number = (int) $number;

        return $number > 0 ? $number : null;
    }

    private function normalizeName(string $name): string
    {
        return Str::lower(preg_replace('/[^a-zA-Z0-9]/', '', Str::ascii($name)));
    }

    private function academicYear(?string $year): string
    {
        $year = trim((string) $year);

        if (! preg_match('/^(\d{2})\/(\d{2})$/', $year, $matches)) {
            throw new RuntimeException('Academic year must use the YY/ZZ format, e.g. 26/27.');
        }

        if (((int) $matches[1] + 1) % 100 !== (int) $matches[2]) {
            throw new RuntimeException('Academic year end must be the year after its start, e.g. 26/27.');
        }

        return $year;
    }

    private function studentIdentifier(string $academicYear, int $grade, string $section, ?int $attendanceNumber): string
    {
        if ($attendanceNumber === null) {
            throw new RuntimeException('Attendance number is required to generate a student identifier.');
        }

        return 'student-'.$this->academicYearPrefix($academicYear).$this->classSectionCode($grade, $section).str_pad((string) $attendanceNumber, 2, '0', STR_PAD_LEFT);
    }

    private function academicYearPrefix(string $academicYear): string
    {
        return str_replace('/', '', $academicYear);
    }

    private function classSectionCode(?int $grade, ?string $section): string
    {
        if ($grade === null || $section === null || ! preg_match('/^[A-I]$/', $section)) {
            throw new RuntimeException('Class and section are required to generate student identifier.');
        }

        return $grade.(ord($section) - 64);
    }

    private function parseClass(string $class): array
    {
        return preg_match('/^(\d+)([A-Z])$/', $class, $matches) ? [(int) $matches[1], $matches[2]] : [null, null];
    }
}
