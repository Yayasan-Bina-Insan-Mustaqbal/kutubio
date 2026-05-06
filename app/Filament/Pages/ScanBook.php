<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class ScanBook extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-camera';

    protected string $view = 'filament.pages.scan-book';

    protected static ?string $navigationLabel = 'Capture Book';

    public static ?string $title = 'Capture Book';

    protected static string|UnitEnum|null $navigationGroup = 'Circulation';

    protected static ?int $navigationSort = 1;

    public ?string $scannedQrCode = null;

    protected function getListeners(): array
    {
        return [
            'qr-scanned' => 'handleQrScanned',
        ];
    }

    public function handleQrScanned(string $value): void
    {
        $this->scannedQrCode = $value;
        
        // You can add logic here to process the QR code, 
        // e.g., finding the book copy and showing details.
    }
}
