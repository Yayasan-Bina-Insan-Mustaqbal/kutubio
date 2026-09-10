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
