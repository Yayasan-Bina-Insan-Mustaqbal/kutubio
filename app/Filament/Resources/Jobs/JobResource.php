<?php

namespace App\Filament\Resources\Jobs;

use App\Enums\JobLogStatus;
use App\Models\JobLog;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Artisan;
use UnitEnum;

class JobResource extends Resource
{
    protected static ?string $model = JobLog::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-queue-list';

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 100;

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Job Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('display_name')
                                    ->label('Job Name'),
                                TextEntry::make('status')
                                    ->badge(),
                                TextEntry::make('queue')
                                    ->badge(),
                                TextEntry::make('job_uuid')
                                    ->label('UUID')
                                    ->fontFamily('mono')
                                    ->copyable(),
                                TextEntry::make('attempts'),
                                TextEntry::make('connection'),
                            ]),
                    ]),

                Section::make('Timestamps')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('queued_at')
                                    ->dateTime(),
                                TextEntry::make('started_at')
                                    ->dateTime()
                                    ->placeholder('Not started'),
                                TextEntry::make('finished_at')
                                    ->dateTime()
                                    ->placeholder('Not finished'),
                            ]),
                    ]),

                Section::make('Exception')
                    ->schema([
                        TextEntry::make('exception')
                            ->label('')
                            ->fontFamily('mono'),
                    ])
                    ->visible(fn (JobLog $record): bool => ! empty($record->exception))
                    ->collapsible(),

                Section::make('Payload')
                    ->schema([
                        TextEntry::make('payload')
                            ->label('')
                            ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT))
                            ->fontFamily('mono'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('display_name')
                    ->label('Job Name')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('queue')
                    ->badge()
                    ->sortable(),
                TextColumn::make('attempts')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('queued_at')
                    ->label('Queued At')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Not started'),
                TextColumn::make('finished_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Not finished'),
                TextColumn::make('exception')
                    ->limit(80)
                    ->wrap()
                    ->tooltip(fn ($state) => $state)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('5s')
            ->filters([
                SelectFilter::make('status')
                    ->options(JobLogStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('retry')
                    ->icon('heroicon-m-arrow-path')
                    ->color('warning')
                    ->visible(fn (JobLog $record): bool => $record->status === JobLogStatus::Failed)
                    ->action(function (JobLog $record): void {
                        Artisan::call('queue:retry', [
                            'id' => [$record->job_uuid],
                        ]);

                        Notification::make()
                            ->title('Job added back to queue')
                            ->success()
                            ->send();
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageJobs::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::query()
            ->whereIn('status', [JobLogStatus::Queued->value, JobLogStatus::Ongoing->value])
            ->count();
    }
}
