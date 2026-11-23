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

        $stored = Storage::disk('public')->putFileAs($directory, $upload, $name, ['visibility' => 'public']);
        $path = $stored ?: $path;

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
