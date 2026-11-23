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

        Storage::disk('public')->makeDirectory($directory, 0775, true, true);

        if ($upload instanceof TemporaryUploadedFile) {
            $stream = $upload->readStream();
            if (! is_resource($stream)) {
                throw new RuntimeException('Unable to open the uploaded book cover for reading.');
            }
            $stored = Storage::disk('public')->put($path, $stream, ['visibility' => 'public']);
            fclose($stream);
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
}
