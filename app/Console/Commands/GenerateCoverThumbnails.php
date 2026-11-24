<?php

namespace App\Console\Commands;

use App\Models\Book;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateCoverThumbnails extends Command
{
    protected $signature = 'kutubio:generate-cover-thumbnails {--width=300} {--force}';

    protected $description = 'Generate ~300px WebP thumbnails for existing book covers';

    public function handle(): int
    {
        $width = (int) $this->option('width');
        $force = $this->option('force');

        $books = Book::whereNotNull('cover_image_path')
            ->where('cover_image_path', '!=', '')
            ->get();

        $generated = 0;
        $skipped = 0;
        $failed = 0;

        $bar = $this->createProgressBar(count($books));
        $bar->start();

        foreach ($books as $book) {
            $sourcePath = $book->coverImagePath();
            if (! $sourcePath || ! Storage::disk('public')->exists($sourcePath)) {
                $skipped++;
                $bar->advance();

                continue;
            }

            $thumbPath = $this->thumbnailPath($book->public_id);

            if (! $force && Storage::disk('public')->exists($thumbPath)) {
                $skipped++;
                $bar->advance();

                continue;
            }

            try {
                $this->generateThumbnail(Storage::disk('public')->path($sourcePath), $thumbPath, $width);
                $generated++;
            } catch (\Throwable $e) {
                $failed++;
                $this->warn("\nFailed for book {$book->public_id}: ".$e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info(sprintf(
            'Thumbnails: %d generated, %d skipped, %d failed',
            $generated,
            $skipped,
            $failed,
        ));

        return self::SUCCESS;
    }

    private function thumbnailPath(string $publicId): string
    {
        return 'book-covers/'.$publicId.'/thumb.webp';
    }

    private function generateThumbnail(string $sourcePath, string $thumbPath, int $width): void
    {
        $sourceImage = imagecreatefromstring(file_get_contents($sourcePath));

        if ($sourceImage === false) {
            throw new \RuntimeException('Unable to decode source image');
        }

        $sourceWidth = imagesx($sourceImage);
        $sourceHeight = imagesy($sourceImage);

        if ($sourceWidth < 1 || $sourceHeight < 1) {
            imagedestroy($sourceImage);
            throw new \RuntimeException('Invalid source image dimensions');
        }

        $scale = min(1, $width / max($sourceWidth, $sourceHeight));
        $targetWidth = max(1, (int) round($sourceWidth * $scale));
        $targetHeight = max(1, (int) round($sourceHeight * $scale));
        $targetImage = imagecreatetruecolor($targetWidth, $targetHeight);

        imagecopyresampled(
            $targetImage,
            $sourceImage,
            0, 0, 0, 0,
            $targetWidth, $targetHeight,
            $sourceWidth, $sourceHeight,
        );

        $directory = dirname($thumbPath);
        if (! Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory, 0775, true, true);
        }

        $success = false;

        if (function_exists('imagewebp')) {
            ob_start();
            $success = imagewebp($targetImage, null, 85);
            $webpBytes = (string) ob_get_clean();
            if ($success) {
                Storage::disk('public')->put($thumbPath, $webpBytes);
                Storage::disk('public')->setVisibility($thumbPath, 'public');
            }
        }

        if (! $success && function_exists('imagejpeg')) {
            ob_start();
            $success = imagejpeg($targetImage, null, 85);
            $jpgBytes = (string) ob_get_clean();
            if ($success) {
                $jpgThumbPath = str_replace('.webp', '.jpg', $thumbPath);
                Storage::disk('public')->put($jpgThumbPath, $jpgBytes);
                Storage::disk('public')->setVisibility($jpgThumbPath, 'public');
            }
        }

        imagedestroy($sourceImage);
        imagedestroy($targetImage);

        if (! $success) {
            throw new \RuntimeException('Unable to generate thumbnail (no WebP/JPEG support)');
        }
    }
}
