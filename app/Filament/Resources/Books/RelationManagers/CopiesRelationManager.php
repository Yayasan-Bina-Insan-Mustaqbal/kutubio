<?php

namespace App\Filament\Resources\Books\RelationManagers;

use App\Enums\BookCopyStatus;
use App\Models\PrintProfile;
use App\Services\PrintService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
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
                    ->options(BookCopyStatus::class)
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
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                Action::make('print_sticker')
                    ->label('Sticker')
                    ->icon('heroicon-o-printer')
                    ->form([
                        Select::make('profile_id')
                            ->label('Print Profile')
                            ->options(PrintProfile::pluck('name', 'id'))
                            ->default(fn () => PrintProfile::where('is_default', true)->first()?->id ?? PrintProfile::first()?->id)
                            ->required(),
                    ])
                    ->action(function ($record, array $data, PrintService $printService) {
                        $profile = PrintProfile::findOrFail($data['profile_id']);
                        $pdf = $printService->generateStickerSheet(collect([$record]), $profile);

                        $filename = Str::uuid()->toString().'.pdf';
                        $originalName = "sticker-{$record->public_id}.pdf";
                        Storage::disk('local')->put('temp-pdfs/'.$filename, $pdf);

                        return redirect()->away(
                            URL::signedRoute('download.temp', ['filename' => $filename, 'name' => $originalName])
                        );
                    }),
                Action::make('print_card')
                    ->label('Card')
                    ->icon('heroicon-o-identification')
                    ->action(function ($record, PrintService $printService) {
                        $pdf = $printService->generateBookCards(collect([$record]));

                        $filename = Str::uuid()->toString().'.pdf';
                        $originalName = "book-card-{$record->public_id}.pdf";
                        Storage::disk('local')->put('temp-pdfs/'.$filename, $pdf);

                        return redirect()->away(
                            URL::signedRoute('download.temp', ['filename' => $filename, 'name' => $originalName])
                        );
                    }),
                DeleteAction::make(),
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
}
