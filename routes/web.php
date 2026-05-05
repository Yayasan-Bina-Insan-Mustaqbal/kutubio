<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::view('/', 'welcome')->name('home');

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
