<?php

use App\Http\Controllers\PublicBookCatalogController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', [PublicBookCatalogController::class, 'index'])->name('home');
Route::get('/catalog/{book:public_id}', [PublicBookCatalogController::class, 'show'])->name('catalog.books.show');

Route::get('/public-files/{path}', function (string $path) {
    abort_unless(str_starts_with($path, 'book-covers/'), 404);
    abort_unless(Storage::disk('public')->exists($path), 404);

    $contents = Storage::disk('public')->get($path);
    $mime = Storage::disk('public')->mimeType($path) ?: 'application/octet-stream';

    return response()->stream(function () use ($contents): void {
        echo $contents;
    }, 200, [
        'Content-Type' => $mime,
        'Cache-Control' => 'public, max-age=31536000, immutable',
    ]);
})->where('path', '.*')->name('storage.public');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

/**
 * Signed temporary PDF download route.
 * Filament actions store the PDF to temp storage then redirect here.
 */
Route::get('/download/temp/{filename}', function (Request $request, string $filename) {
    abort_unless($request->hasValidSignature(), 403);

    $path = 'temp-pdfs/'.$filename;

    abort_unless(Storage::disk('local')->exists($path), 404);

    $originalName = $request->query('name', $filename);

    return response()->download(
        Storage::disk('local')->path($path),
        $originalName,
        ['Content-Type' => 'application/pdf']
    )->deleteFileAfterSend(true);
})->name('download.temp')->middleware('signed');

require __DIR__.'/settings.php';
