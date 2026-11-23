<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Http\UploadedFile;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Support\Facades\Log;
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

        Log::info('BookCoverStorage storeDraft', [
            'book_id' => $book->id,
            'public_id' => $book->public_id,
            'directory' => $directory,
            'path' => $path,
            'upload_class' => get_class($upload),
            'upload_exists' => method_exists($upload, 'exists') ? $upload->exists() : 'n/a',
            'upload_real_path' => method_exists($upload, 'getRealPath') ? $upload->getRealPath() : 'n/a',
        ]);

        $stored = Storage::disk('public')->putFileAs($directory, $upload, $name, ['visibility' => 'public']);
        $path = $stored ?: $path;

        Log::info('BookCoverStorage storeDraft result', [
            'book_id' => $book->id,
            'stored' => $stored,
            'final_path' => $path,
        ]);

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
