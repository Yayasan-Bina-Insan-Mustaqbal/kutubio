<?php

namespace Tests\Unit;

use App\Services\PrintService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PrintServiceTest extends TestCase
{
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
