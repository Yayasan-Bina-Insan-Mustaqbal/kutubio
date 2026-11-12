<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table): void {
            $table->string('cover_image_path')->nullable()->after('metadata_lookup_attempted_at');
        });

        DB::table('metadata_revisions')
            ->select(['book_id', 'capture_session_id'])
            ->whereNotNull('book_id')
            ->whereNotNull('capture_session_id')
            ->orderBy('id')
            ->get()
            ->each(function (object $revision): void {
                $book = DB::table('books')->where('id', $revision->book_id)->first(['id', 'public_id', 'cover_image_path']);
                $captureSession = DB::table('capture_sessions')->where('id', $revision->capture_session_id)->first(['front_image_path']);
                $sourcePath = $captureSession?->front_image_path;

                if (! $book || filled($book->cover_image_path) || ! filled($sourcePath) || ! str_starts_with($sourcePath, 'capture-sessions/')) {
                    return;
                }

                $disk = Storage::disk('public');
                if (! $disk->exists($sourcePath)) {
                    return;
                }

                $extension = pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'jpg';
                $destinationPath = 'book-covers/'.$book->public_id.'/capture-'.$revision->capture_session_id.'.'.$extension;
                $stream = $disk->readStream($sourcePath);

                if (! is_resource($stream)) {
                    return;
                }

                try {
                    $stored = $disk->writeStream($destinationPath, $stream, ['visibility' => 'public']);
                } finally {
                    fclose($stream);
                }

                if ($stored) {
                    DB::table('books')->where('id', $book->id)->whereNull('cover_image_path')->update([
                        'cover_image_path' => $destinationPath,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table): void {
            $table->dropColumn('cover_image_path');
        });
    }
};
