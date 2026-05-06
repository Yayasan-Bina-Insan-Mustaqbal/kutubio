<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class ScanBook extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-camera';

    protected static string $view = 'filament.pages.scan-book';

    protected static ?string $navigationLabel = 'Capture Book';

    public static ?string $title = 'Capture Book';

    protected static ?string $navigationGroup = 'Circulation';

    protected static ?int $navigationSort = 1;
}
