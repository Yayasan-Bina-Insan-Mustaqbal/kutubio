<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Http\UploadedFile;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class BookCoverStorage
{
    public function storeDraft(Book $book, UploadedFile|TemporaryUploadedFile $upload): string
    {
        $directory = 'book-covers/' . $book->public_id;
        $extension = $upload->guessExtension() ?: 'jpg';
        $name = Str::ulid() . '.' . $extension;
        $path = $directory . '/' . $name;

        if ($upload instanceof TemporaryUploadedFile) {
            $stream = $upload->readStream();
            $stored = Storage::disk('public')->put($path, $stream, ['visibility' => 'public']);
            if (is_resource($stream)) {
                fclose($stream);
            }
        } else {
            $stored = $upload->storePubliclyAs($directory, $name, 'public');
            $path = $stored ?: $path;
        }

        if (! $stored) {
            throw new RuntimeException('Unable to store the uploaded book cover.');
        }

        return $path;
    }

    public function remove(string $path): void
    {
        Storage::disk('public')->delete($path);
    }

    public function replace(Book $book, UploadedFile|TemporaryUploadedFile $upload): string
    {
        $oldPath = $book->cover_image_path;
        $path = $this->storeDraft($book, $upload);

        try {
            $book->forceFill(['cover_image_path' => $path])->save();
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($path);

            throw $exception;
        }

        if (filled($oldPath) && str_starts_with($oldPath, 'book-covers/' . $book->public_id . '/') && $oldPath !== $path) {
            Storage::disk('public')->delete($oldPath);
        }

        return $path;
    }
}
