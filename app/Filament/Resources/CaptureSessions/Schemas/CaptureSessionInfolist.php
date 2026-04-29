<?php

namespace App\Filament\Resources\CaptureSessions\Schemas;

use App\Enums\CaptureSessionStatus;
use App\Filament\Resources\Books\BookResource;
use App\Jobs\ExtractBookDataWithVisionJob;
use App\Jobs\PersistCaptureSessionJob;
use App\Jobs\ReadIsbnQrCodeJob;
use App\Jobs\SummarizeCaptureSessionJob;
use Filament\Actions\Action;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Bus;

class CaptureSessionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Capture evidence')
                    ->schema([
                        ImageEntry::make('front_image_path')
                            ->label('Front image')
                            ->disk('public')
                            ->placeholder('Not uploaded'),
                        ImageEntry::make('back_image_path')
                            ->label('Back image')
                            ->disk('public')
                            ->placeholder('Not uploaded'),
                        TextEntry::make('decoded_qr_payload')
                            ->copyable()
                            ->placeholder('None')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Resulting Metadata')
                    ->schema([
                        TextEntry::make('extracted_title')
                            ->label('Title')
                            ->getStateUsing(fn ($record) => $record->metadataRevisions()->latest()->first()?->payload['title'] ?? 'N/A')
                            ->weight('bold')
                            ->columnSpanFull(),
                        TextEntry::make('extracted_authors')
                            ->label('Authors')
                            ->getStateUsing(fn ($record) => implode(', ', (array) ($record->metadataRevisions()->latest()->first()?->payload['authors'] ?? [])))
                            ->placeholder('None'),
                        TextEntry::make('extracted_isbn')
                            ->label('ISBN/QR')
                            ->getStateUsing(fn ($record) => $record->metadataRevisions()->where('source_stage', 'qr_reading')->latest()->first()?->payload['decoded_text'] ?? 'Not found'),
                        TextEntry::make('created_book')
                            ->label('Book Created')
                            ->getStateUsing(fn ($record) => $record->metadataRevisions()->whereNotNull('book_id')->first()?->book?->title ?? 'None')
                            ->color('success')
                            ->url(fn ($record) => $record->metadataRevisions()->whereNotNull('book_id')->first() ? BookResource::getUrl('view', ['record' => $record->metadataRevisions()->whereNotNull('book_id')->first()->book_id]) : null),
                        TextEntry::make('extracted_summary')
                            ->label('AI Summary')
                            ->getStateUsing(fn ($record) => $record->metadataRevisions()->where('source_stage', 'final_summary')->latest()->first()?->payload['summary'] ?? 'No summary yet')
                            ->columnSpanFull()
                            ->prose(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(fn ($record) => $record->metadataRevisions()->count() === 0),

                Section::make('Processing actions')
                    ->schema([
                        Actions::make([
                            Action::make('extractTitle')
                                ->label(fn ($record) => 'Extract Title'.($record->metadataRevisions()->where('source_stage', 'vision_extraction')->exists() ? ' ✅' : ''))
                                ->icon('heroicon-m-sparkles')
                                ->color(fn ($record) => $record->metadataRevisions()->where('source_stage', 'vision_extraction')->exists() ? 'success' : 'info')
                                ->action(function ($record) {
                                    ExtractBookDataWithVisionJob::dispatch($record);
                                    Notification::make()->title('Title extraction dispatched')->success()->send();
                                })
                                ->disabled(fn ($record) => ! $record->front_image_path),

                            Action::make('readIsbnQr')
                                ->label(fn ($record) => 'Read ISBN QR'.($record->metadataRevisions()->where('source_stage', 'qr_reading')->exists() ? ' ✅' : ''))
                                ->icon('heroicon-m-qr-code')
                                ->color(fn ($record) => $record->metadataRevisions()->where('source_stage', 'qr_reading')->exists() ? 'success' : 'info')
                                ->action(function ($record) {
                                    ReadIsbnQrCodeJob::dispatch($record);
                                    Notification::make()->title('QR reading dispatched')->success()->send();
                                })
                                ->disabled(fn ($record) => ! $record->back_image_path),

                            Action::make('summarize')
                                ->label(fn ($record) => 'Final Summary'.($record->metadataRevisions()->where('source_stage', 'final_summary')->exists() ? ' ✅' : ''))
                                ->icon('heroicon-m-document-text')
                                ->color(fn ($record) => $record->metadataRevisions()->where('source_stage', 'final_summary')->exists() ? 'success' : 'info')
                                ->action(function ($record) {
                                    SummarizeCaptureSessionJob::dispatch($record);
                                    Notification::make()->title('Summary generation dispatched')->success()->send();
                                }),

                            Action::make('persist')
                                ->label(fn ($record) => 'Persist Records'.($record->status === CaptureSessionStatus::Approved ? ' ✅' : ''))
                                ->icon('heroicon-m-archive-box-arrow-down')
                                ->color(fn ($record) => $record->status === CaptureSessionStatus::Approved ? 'success' : 'warning')
                                ->action(function ($record) {
                                    PersistCaptureSessionJob::dispatch($record);
                                    Notification::make()->title('Persistence dispatched')->success()->send();
                                })
                                ->requiresConfirmation()
                                ->disabled(fn ($record) => $record->status === CaptureSessionStatus::Processing || ! $record->metadataRevisions()->where('source_stage', 'vision_extraction')->exists()),

                            Action::make('processAll')
                                ->label('Process All (Full Chain)')
                                ->icon('heroicon-m-rocket-launch')
                                ->color('primary')
                                ->action(function ($record) {
                                    $record->update(['status' => CaptureSessionStatus::Processing]);

                                    Bus::chain([
                                        new ExtractBookDataWithVisionJob($record),
                                        new ReadIsbnQrCodeJob($record),
                                        new SummarizeCaptureSessionJob($record),
                                        new PersistCaptureSessionJob($record),
                                    ])->dispatch();

                                    Notification::make()->title('Full automation pipeline dispatched')->success()->send();
                                })
                                ->requiresConfirmation()
                                ->disabled(fn ($record) => ! $record->front_image_path || ! $record->back_image_path),
                        ])
                            ->key('processingActions')
                            ->columnSpanFull(),

                        Grid::make(3)
                            ->schema([
                                TextEntry::make('status')
                                    ->badge(),
                                TextEntry::make('current_processing_stage')
                                    ->label('Current Stage')
                                    ->placeholder('Idle')
                                    ->visible(fn ($record) => $record->status === CaptureSessionStatus::Processing),
                                TextEntry::make('duration')
                                    ->label('Processing Time')
                                    ->getStateUsing(fn ($record) => $record->processing_started_at ?
                                        ($record->processing_finished_at ?
                                            $record->processing_started_at->diffForHumans($record->processing_finished_at, true) :
                                            $record->processing_started_at->diffForHumans(now(), true).' (running)') :
                                        'N/A'),
                                TextEntry::make('qr_parse_status')
                                    ->badge()
                                    ->placeholder('Not parsed'),
                                TextEntry::make('metadata_revisions_count')
                                    ->label('Revisions')
                                    ->numeric(),
                                TextEntry::make('failure_reason')
                                    ->label('Failure Reason')
                                    ->placeholder('None')
                                    ->columnSpanFull()
                                    ->color('danger')
                                    ->weight('bold')
                                    ->prose(),
                            ]),
                    ])
                    ->columns(1),

                Section::make('Job log')
                    ->schema([
                        RepeatableEntry::make('jobLogs')
                            ->label('Jobs')
                            ->placeholder('No jobs have been logged for this capture session yet.')
                            ->table([
                                TableColumn::make('Job'),
                                TableColumn::make('Status'),
                                TableColumn::make('Attempts'),
                                TableColumn::make('Queued'),
                                TableColumn::make('Started'),
                                TableColumn::make('Finished'),
                            ])
                            ->schema([
                                TextEntry::make('display_name')
                                    ->label('Job')
                                    ->formatStateUsing(fn (string $state): string => str($state)->afterLast('\\')->toString()),
                                TextEntry::make('status')
                                    ->badge(),
                                TextEntry::make('attempts')
                                    ->numeric(),
                                TextEntry::make('queued_at')
                                    ->dateTime()
                                    ->placeholder('None'),
                                TextEntry::make('started_at')
                                    ->dateTime()
                                    ->placeholder('Not started'),
                                TextEntry::make('finished_at')
                                    ->dateTime()
                                    ->placeholder('Not finished'),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Section::make('Session')
                    ->schema([
                        TextEntry::make('public_id')
                            ->label('Public ID')
                            ->copyable(),
                        TextEntry::make('submittedBy.name')
                            ->label('Submitted by')
                            ->placeholder('System'),
                        TextEntry::make('quantity')
                            ->numeric(),
                        TextEntry::make('submitted_at')
                            ->dateTime()
                            ->placeholder('None'),
                        TextEntry::make('processing_started_at')
                            ->dateTime()
                            ->placeholder('None'),
                        TextEntry::make('processing_finished_at')
                            ->dateTime()
                            ->placeholder('None'),
                    ])
                    ->columns(3),
            ]);
    }
}
