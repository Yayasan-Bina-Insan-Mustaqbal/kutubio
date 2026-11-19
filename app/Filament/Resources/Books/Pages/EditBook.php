<?php

namespace App\Filament\Resources\Books\Pages;

use App\Filament\Resources\Books\BookResource;
use App\Models\Book;
use App\Services\BookCoverStorage;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditBook extends EditRecord
{
    protected static string $resource = BookResource::class;

    protected ?string $oldCoverPath = null;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $coverState = $data['cover_image_path'] ?? null;
        $newCoverPath = collect(is_array($coverState) ? $coverState : [$coverState])
            ->filter(fn (mixed $path): bool => is_string($path)
                && str_starts_with($path, 'book-covers/'.$this->record->public_id.'/')
                && $path !== $this->record->cover_image_path)
            ->last();

        if ($newCoverPath) {
            $this->oldCoverPath = $this->record->cover_image_path;
            $data['cover_image_path'] = $newCoverPath;

            return $data;
        }

        $data['cover_image_path'] = $this->record->cover_image_path;

        return $data;
    }

    protected function afterSave(): void
    {
        if (filled($this->oldCoverPath)
            && $this->oldCoverPath !== $this->record->cover_image_path
            && str_starts_with($this->oldCoverPath, 'book-covers/')) {
            app(BookCoverStorage::class)->remove($this->oldCoverPath);
        }

        $addCopies = (int) ($this->data['add_copies'] ?? 0);

        if ($addCopies > 0) {
            for ($i = 0; $i < $addCopies; $i++) {
                $this->record->copies()->create([
                    'status' => \App\Enums\BookCopyStatus::Draft,
                    'acquired_at' => now(),
                ]);
            }

            Notification::make()
                ->title("Added {$addCopies} new copies")
                ->success()
                ->send();

            // Refresh the form to show updated count and reset add_copies
            $this->fillForm();
        }
    }
}
