<?php

namespace App\Filament\Resources\Jobs\Pages;

use App\Filament\Resources\Jobs\JobResource;
use Filament\Resources\Pages\ManageRecords;

class ManageJobs extends ManageRecords
{
    protected static string $resource = JobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('refresh')
                ->label('Refresh Logs')
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => null),
        ];
    }
}
