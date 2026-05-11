<?php

namespace App\Filament\Resources\CaptureSessions\Tables;

use App\Enums\CaptureSessionStatus;
use App\Enums\QrParseStatus;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CaptureSessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('public_id')
                    ->label('Session')
                    ->searchable()
                    ->copyable()
                    ->limit(12),
                ImageColumn::make('front_image_path')
                    ->label('Front')
                    ->disk('public')
                    ->square(),
                ImageColumn::make('back_image_path')
                    ->label('Back')
                    ->disk('public')
                    ->square(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('qr_parse_status')
                    ->badge()
                    ->placeholder('Not parsed'),
                TextColumn::make('submittedBy.name')
                    ->label('Submitted by')
                    ->placeholder('System')
                    ->toggleable(),
                TextColumn::make('metadata_revisions_count')
                    ->label('Revisions')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('submitted_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('None'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(CaptureSessionStatus::class),
                SelectFilter::make('qr_parse_status')
                    ->options(QrParseStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
