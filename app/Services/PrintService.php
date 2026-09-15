<?php

namespace App\Services;

use App\Models\GeneralSetting;
use App\Models\Loan;
use App\Models\PrintProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\View;
use RuntimeException;

final class PrintService
{
    /**
     * Render HTML to PDF using Gotenberg Chromium.
     */
    public function renderPdfFromHtml(string $html, array $options = []): string
    {
        $endpoint = config('services.gotenberg.endpoint', env('GOTENBERG_ENDPOINT', 'http://gotenberg:3000'));

        $response = Http::asMultipart()
            ->attach('index.html', $html, 'index.html')
            ->post("{$endpoint}/forms/chromium/convert/html", array_merge([
                'paperWidth' => '8.27', // A4
                'paperHeight' => '11.7',
                'marginTop' => '0',
                'marginBottom' => '0',
                'marginLeft' => '0',
                'marginRight' => '0',
            ], $options));

        if ($response->failed()) {
            throw new RuntimeException('Gotenberg PDF rendering failed: '.$response->body());
        }

        return $response->body();
    }

    /**
     * Generate a sticker sheet PDF.
     */
    public function generateStickerSheet(Collection $items, PrintProfile $profile, int $skipSlots = 0): string
    {
        $settings = GeneralSetting::find(1);
        $libraryName = $settings?->library_name ?? 'Kutubio Library';

        $html = View::make('print.sticker-sheet', [
            'items' => $items,
            'profile' => $profile,
            'skipSlots' => $skipSlots,
            'libraryName' => $libraryName,
        ])->render();

        return $this->renderPdfFromHtml($html, [
            'paperWidth' => number_format($profile->page_width_mm / 25.4, 2),
            'paperHeight' => number_format($profile->page_height_mm / 25.4, 2),
        ]);
    }

    /**
     * Generate book cards PDF.
     */
    public function generateBookCards(Collection $items): string
    {
        $settings = GeneralSetting::find(1);
        $libraryName = $settings?->library_name ?? 'Kutubio Library';

        $html = View::make('print.book-card', [
            'items' => $items,
            'libraryName' => $libraryName,
            'settings' => $settings,
        ])->render();

        return $this->renderPdfFromHtml($html);
    }

    /**
     * Generate a loan report PDF for the current selection.
     */
    public function generateLoanList(Collection $items): string
    {
        $settings = GeneralSetting::find(1);
        $libraryName = $settings?->library_name ?? 'Kutubio Library';

        $items = $items
            ->filter(fn ($item) => $item instanceof Loan)
            ->sortBy([
                ['bookCopy.book.title', 'asc'],
                ['borrower.class', 'asc'],
                ['status', 'asc'],
            ])
            ->values();

        $html = View::make('print.loan-list', [
            'items' => $items,
            'libraryName' => $libraryName,
            'generatedAt' => now(),
        ])->render();

        return $this->renderPdfFromHtml($html, [
            'paperWidth' => '8.27',
            'paperHeight' => '11.7',
        ]);
    }
}
