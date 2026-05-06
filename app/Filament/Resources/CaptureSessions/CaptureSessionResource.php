<?php

namespace App\Filament\Resources\CaptureSessions;

use App\Filament\Resources\CaptureSessions\Pages\EditCaptureSession;
use App\Filament\Resources\CaptureSessions\Pages\ListCaptureSessions;
use App\Filament\Resources\CaptureSessions\Pages\ViewCaptureSession;
use App\Filament\Resources\CaptureSessions\Schemas\CaptureSessionForm;
use App\Filament\Resources\CaptureSessions\Schemas\CaptureSessionInfolist;
use App\Filament\Resources\CaptureSessions\Tables\CaptureSessionsTable;
use App\Models\CaptureSession;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CaptureSessionResource extends Resource
{
    protected static ?string $model = CaptureSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Capture Sessions';

    protected static string|UnitEnum|null $navigationGroup = 'Intake';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'public_id';

    public static function form(Schema $schema): Schema
    {
        return CaptureSessionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CaptureSessionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CaptureSessionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCaptureSessions::route('/'),
            'view' => ViewCaptureSession::route('/{record}'),
            'edit' => EditCaptureSession::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['jobLogs', 'submittedBy'])
            ->withCount('metadataRevisions');
    }
}
