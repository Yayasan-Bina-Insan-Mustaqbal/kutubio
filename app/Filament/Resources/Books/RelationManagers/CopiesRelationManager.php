<?php

namespace App\Filament\Resources\Books\RelationManagers;

use App\Enums\BookCopyStatus;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\PrintProfile;
use App\Services\PrintService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class CopiesRelationManager extends RelationManager
{
    protected static string $relationship = 'copies';

    protected static ?string $recordTitleAttribute = 'public_id';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('public_id')
                    ->disabled()
                    ->dehydrated(false),
                Select::make('status')
                    ->options(fn (?BookCopy $record): array => collect(BookCopyStatus::cases())
                        ->reject(fn (BookCopyStatus $status): bool => $status === BookCopyStatus::Borrowed && $record?->status !== BookCopyStatus::Borrowed)
                        ->mapWithKeys(fn (BookCopyStatus $status): array => [$status->value => $status->getLabel() ?? $status->value])
                        ->all())
                    ->disabled(fn (?BookCopy $record): bool => $record?->status === BookCopyStatus::Borrowed)
                    ->dehydrated(fn (?BookCopy $record): bool => $record?->status !== BookCopyStatus::Borrowed)
                    ->required(),
                Select::make('funding_source')
                    ->label('Funding Source')
                    ->options([
                        'self' => 'Self-Fund',
                        'BOSP' => 'BOSP (Gov-Fund)',
                    ])
                    ->required(),
                Select::make('purchase_year')
                    ->label('Year of Purchase')
                    ->options([
                        'Old Collection' => 'Old Collection',
                    ] + collect(range(2023, 2030))->mapWithKeys(fn (int $year): array => [(string) $year => (string) $year])->all())
                    ->required(),
                TextInput::make('tracking_code')
                    ->maxLength(255),
                DatePicker::make('acquired_at'),
                Textarea::make('location_note')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->selectable()
            ->columns([
                TextColumn::make('public_id')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('tracking_code')
                    ->placeholder('None'),
                TextColumn::make('acquired_at')
                    ->date()
                    ->sortable(),
            ])
            ->headerActions([
                Action::make('create_copy')
                    ->label('New book copy')
                    ->schema([
                        TextInput::make('quantity')
                            ->label('Number of copies')
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->required(),
                Select::make('status')
                    ->options(fn (?BookCopy $record): array => collect(BookCopyStatus::cases())
                        ->reject(fn (BookCopyStatus $status): bool => $status === BookCopyStatus::Borrowed && $record?->status !== BookCopyStatus::Borrowed)
                        ->mapWithKeys(fn (BookCopyStatus $status): array => [$status->value => $status->getLabel() ?? $status->value])
                        ->all())
                    ->disabled(fn (?BookCopy $record): bool => $record?->status === BookCopyStatus::Borrowed)
                    ->dehydrated(fn (?BookCopy $record): bool => $record?->status !== BookCopyStatus::Borrowed)
                    ->required(),
                        Select::make('funding_source')
                            ->label('Funding Source')
                            ->options([
                                'self' => 'Self-Fund',
                                'BOSP' => 'BOSP (Gov-Fund)',
                            ])
                            ->default('self')
                            ->required(),
                        Select::make('purchase_year')
                            ->label('Year of Purchase')
                            ->options([
                                'Old Collection' => 'Old Collection',
                            ] + collect(range(2023, 2030))->mapWithKeys(fn (int $year): array => [(string) $year => (string) $year])->all())
                            ->default('Old Collection')
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $ownerRecord = $this->getOwnerRecord();

                        for ($i = 0; $i < (int) $data['quantity']; $i++) {
                            $ownerRecord->copies()->create([
                                'status' => $data['status'],
                                'funding_source' => $data['funding_source'],
                                'purchase_year' => $data['purchase_year'],
                                'acquired_at' => now(),
                            ]);
                        }
                    }),
            ])
            ->bulkActions([
                BulkAction::make('edit_selected')
                    ->label('Edit Selected')
                    ->icon('heroicon-o-pencil-square')
                    ->fillForm(function (BulkAction $action): array {
                        $records = $action->getSelectedRecords();
                        $status = BookCopy::uniformStoredValue($records, 'status');

                        return [
                            'public_ids' => $records->pluck('public_id')->join(', '),
                            'qr_payload' => $records->pluck('qr_payload')->join(', '),
                            'status' => $status === BookCopyStatus::Borrowed->value ? null : $status,
                            'funding_source' => BookCopy::uniformStoredValue($records, 'funding_source'),
                            'purchase_year' => BookCopy::uniformStoredValue($records, 'purchase_year'),
                            'tracking_code' => BookCopy::uniformStoredValue($records, 'tracking_code'),
                            'acquired_at' => BookCopy::uniformStoredValue($records, 'acquired_at'),
                            'location_note' => BookCopy::uniformStoredValue($records, 'location_note'),
                        ];
                    })
                    ->accessSelectedRecords()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('public_ids')
                                    ->label('Public ID')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columnSpanFull(),
                                Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        BookCopyStatus::Draft->value => BookCopyStatus::Draft->getLabel(),
                                        BookCopyStatus::Available->value => BookCopyStatus::Available->getLabel(),
                                        BookCopyStatus::Processing->value => BookCopyStatus::Processing->getLabel(),
                                        BookCopyStatus::Lost->value => BookCopyStatus::Lost->getLabel(),
                                        BookCopyStatus::Archived->value => BookCopyStatus::Archived->getLabel(),
                                    ])
                                    ->placeholder('Select status'),
                                Select::make('funding_source')
                                    ->label('Funding Source')
                                    ->options(['self' => 'Self-Fund', 'BOSP' => 'BOSP (Gov-Fund)']),
                                Select::make('purchase_year')
                                    ->label('Year of Purchase')
                                    ->options(['Old Collection' => 'Old Collection'] + collect(range(2023, 2030))->mapWithKeys(fn (int $year): array => [(string) $year => (string) $year])->all()),
                                TextInput::make('tracking_code')->label('Tracking Code')->maxLength(255),
                                TextInput::make('qr_payload')
                                    ->label('QR Payload')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columnSpanFull(),
                                DatePicker::make('acquired_at')->label('Acquired At'),
                                Textarea::make('location_note')->label('Location Note')->columnSpanFull(),
                            ]),
                    ])
                    ->authorizeIndividualRecords(false)
                    ->action(function (Collection $records, array $data): void {
                        $updates = [];

                        foreach (['funding_source', 'purchase_year', 'tracking_code', 'acquired_at', 'location_note'] as $field) {
                            if (filled($data[$field] ?? null)) {
                                $updates[$field] = $data[$field];
                            }
                        }

                        $availabilityStatus = filled($data['status'] ?? null)
                            ? BookCopyStatus::from($data['status'])
                            : null;

                        foreach ($records as $record) {
                            $record->applyBulkUpdates($updates, $availabilityStatus);
                        }

                        if ($updates === [] && $availabilityStatus === null) {
                            Notification::make()
                                ->title('No fields selected for update')
                                ->warning()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Selected copies updated')
                            ->body('Borrowed copies were skipped for availability changes.')
                            ->success()
                            ->send();
                    }),
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
                DeleteBulkAction::make()
                    ->label('Delete Selected')
                    ->authorizeIndividualRecords(false),
            ]);
    }
}
