<?php

namespace Tests\Unit;

use App\Models\BookCopy;
use App\Services\PrintService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PrintServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sticker_pagination_counts_initial_skipped_slots_as_used_capacity(): void
    {
        $items = collect(range(1, 5));

        $pages = PrintService::paginateStickerItems($items, 12, 9);

        $this->assertCount(2, $pages);
        $this->assertCount(3, $pages[0]['items']);
        $this->assertSame(9, $pages[0]['skipSlots']);
        $this->assertCount(2, $pages[1]['items']);
        $this->assertSame(0, $pages[1]['skipSlots']);
    }

    public function test_sticker_pagination_fills_subsequent_pages_after_initial_gap(): void
    {
        $items = collect(range(1, 24));

        $pages = PrintService::paginateStickerItems($items, 12, 1);

        $this->assertCount(3, $pages);
        $this->assertCount(11, $pages[0]['items']);
        $this->assertSame(1, $pages[0]['skipSlots']);
        $this->assertCount(12, $pages[1]['items']);
        $this->assertCount(1, $pages[2]['items']);
    }

    public function test_it_generates_book_cards_for_book_copies(): void
    {
        Http::fake([
            '*/forms/chromium/convert/html' => Http::response('fake-pdf-content', 200),
        ]);

        $copy = BookCopy::factory()->create();
        $result = (new PrintService())->generateBookCards(collect([$copy]));

        $this->assertSame('fake-pdf-content', $result);
    }

    public function test_it_calls_gotenberg_to_render_pdf(): void
    {
        Http::fake([
            '*/forms/chromium/convert/html' => Http::response('fake-pdf-content', 200),
        ]);

        $service = new PrintService();
        $result = $service->renderPdfFromHtml('<h1>Test</h1>');

        $this->assertEquals('fake-pdf-content', $result);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/forms/chromium/convert/html') &&
                $request->isMultipart();
        });
    }
}
