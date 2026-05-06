<?php

namespace App\Filament\Resources\Borrowers\Pages;

use App\Filament\Resources\Borrowers\BorrowerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBorrowers extends ListRecords
{
    protected static string $resource = BorrowerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('sync_surreal')
                ->label('Sync with School DB')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->requiresConfirmation()
                ->action(function () {
                    \App\Jobs\SyncBorrowersFromSurrealDbJob::dispatch();
                    \Filament\Notifications\Notification::make()
                        ->title('Sync Started')
                        ->body('The borrower synchronization job has been dispatched to the queue.')
                        ->info()
                        ->send();
                }),
            Actions\CreateAction::make(),
        ];
    }
}
