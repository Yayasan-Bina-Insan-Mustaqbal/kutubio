<?php

namespace App\Filament\Resources\Books\Pages;

use App\Filament\Resources\Books\BookResource;
use App\Models\Book;
use App\Services\BookCoverStorage;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

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
        $items = is_array($coverState) ? $coverState : [$coverState];

        Log::info('EditBook mutateFormDataBeforeSave cover_state', [
            'book_id' => $this->record->id,
            'public_id' => $this->record->public_id,
            'current_cover' => $this->record->cover_image_path,
            'cover_state' => $coverState,
            'items_count' => count($items),
            'items_types' => array_map(fn ($i) => get_debug_type($i), $items),
        ]);

        // Store newly uploaded temporary file (from Livewire file input).
        foreach ($items as $item) {
            if ($item instanceof TemporaryUploadedFile) {
                Log::info('EditBook storing temporary upload', [
                    'book_id' => $this->record->id,
                    'file_class' => get_class($item),
                    'file_exists' => $item->exists(),
                    'file_path' => $item->getRealPath(),
                ]);

                $this->oldCoverPath = $this->record->cover_image_path;
                $data['cover_image_path'] = app(BookCoverStorage::class)->storeDraft($this->record, $item);

                Log::info('EditBook stored temporary upload result', [
                    'book_id' => $this->record->id,
                    'new_cover' => $data['cover_image_path'],
                ]);

                return $data;
            }
        }

        // Keep already-stored path under the book's folder, otherwise preserve current path.
        $newCoverPath = collect($items)
            ->filter(fn (mixed $path): bool => is_string($path)
                && str_starts_with($path, 'book-covers/'.$this->record->public_id.'/')
                && $path !== $this->record->cover_image_path)
            ->last();

        if ($newCoverPath) {
            $this->oldCoverPath = $this->record->cover_image_path;
            $data['cover_image_path'] = $newCoverPath;

            Log::info('EditBook using existing stored path', [
                'book_id' => $this->record->id,
                'new_cover' => $newCoverPath,
                'old_cover' => $this->oldCoverPath,
            ]);

            return $data;
        }

        $data['cover_image_path'] = $this->record->cover_image_path;

        Log::info('EditBook preserving current cover (no new upload)', [
            'book_id' => $this->record->id,
            'cover' => $data['cover_image_path'],
        ]);

        return $data;
    }

    protected function afterSave(): void
    {
        if (filled($this->oldCoverPath)
            && $this->oldCoverPath !== $this->record->cover_image_path
            && str_starts_with($this->oldCoverPath, 'book-covers/')) {
            Log::info('EditBook afterSave removing old cover', [
                'book_id' => $this->record->id,
                'old_cover' => $this->oldCoverPath,
                'new_cover' => $this->record->cover_image_path,
            ]);
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
