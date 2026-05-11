<?php

namespace App\Filament\Resources\CaptureSessions\Schemas;

use App\Enums\CaptureSessionStatus;
use App\Enums\QrParseStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CaptureSessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Operational state')
                    ->schema([
                        Select::make('status')
                            ->options(CaptureSessionStatus::class)
                            ->required(),
                        Select::make('qr_parse_status')
                            ->options(QrParseStatus::class),
                        DateTimePicker::make('submitted_at'),
                        DateTimePicker::make('processing_started_at'),
                        DateTimePicker::make('processing_finished_at'),
                        Textarea::make('decoded_qr_payload')
                            ->rows(2)
                            ->columnSpanFull(),
                        \Filament\Forms\Components\TextInput::make('quantity')
                            ->numeric()
                            ->default(1)
                            ->required(),
                        Textarea::make('failure_reason')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
