<?php

namespace App\Filament\Resources\Books\Pages;

use App\Enums\BookCopyStatus;
use App\Filament\Resources\Books\BookResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditBook extends EditRecord
{
    protected static string $resource = BookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $addCopies = (int) ($this->data['add_copies'] ?? 0);

        if ($addCopies > 0) {
            $status = $this->data['add_copies_status'] ?? \App\Enums\BookCopyStatus::Draft;
            $fundingSource = $this->data['add_copies_funding_source'] ?? 'self';
            $purchaseYear = $this->data['add_copies_purchase_year'] ?? 'Old Collection';

            for ($i = 0; $i < $addCopies; $i++) {
                $this->record->copies()->create([
                    'status' => $status,
                    'funding_source' => $fundingSource,
                    'purchase_year' => $purchaseYear,
                    'acquired_at' => now(),
                ]);
            }

            \Filament\Notifications\Notification::make()
                ->title("Added {$addCopies} new copies")
                ->success()
                ->send();

            // Refresh the form to show updated count and reset add_copies
            $this->fillForm();
        }
    }
}
