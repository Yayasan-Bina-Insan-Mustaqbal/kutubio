<?php

namespace Tests\Unit;

use App\Services\BookCoverOcrService;
use App\Services\OllamaService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class BookCoverOcrServiceTest extends TestCase
{
    public function test_it_derives_main_title_from_glm_ocr_text_response(): void
    {
        $service = new BookCoverOcrService(Mockery::mock(OllamaService::class));

        $metadata = $service->metadataFromResponse([
            'response' => json_encode([
                'text' => "Abdul Kholiq & Bayu Issetyadi\nHUD\nMENUMBUHKAN\nKESADARAN\nBERAMAL\nTelaah dan Panduan Penumbuhan\nKesadaran Beramal Merujuk Pola\nPendidikan Nabawiyyah",
            ]),
        ]);

        $this->assertSame('Kesadaran Beramal', $metadata['title']);
        $this->assertStringStartsWith('Menumbuhkan', $metadata['subtitle']);
        $this->assertSame(['Abdul Kholiq', 'Bayu Issetyadi'], $metadata['authors']);
        $this->assertSame('HUD', $metadata['publisher']);
    }

    public function test_it_resizes_and_compresses_images_before_ocr_request(): void
    {
        Http::fake([
            '*/api/generate' => Http::response([
                'response' => json_encode(['text' => 'SAMPLE TITLE']),
                'done_reason' => 'stop',
            ], 200),
        ]);

        $image = imagecreatetruecolor(2400, 1800);
        imagefilledrectangle($image, 0, 0, 2399, 1799, imagecolorallocate($image, 255, 255, 255));
        ob_start();
        imagepng($image, null, 6);
        $imageBytes = (string) ob_get_clean();
        imagedestroy($image);
        $dataUrl = 'data:image/png;base64,'.base64_encode($imageBytes);

        Storage::fake('public');
        Storage::disk('public')->put('covers/test.jpg', $imageBytes);
        config([
            'services.ollama.url' => 'http://ollama.test:11434',
            'services.ollama.vision_model' => 'glm-ocr',
            'services.ollama.max_image_dimension' => 1200,
        ]);

        $service = new BookCoverOcrService(new OllamaService());
        $service->extractFromStoragePath('covers/test.jpg');

        Http::assertSent(function ($request): bool {
            $imageBytes = base64_decode($request['images'][0], strict: true);
            $decodedImage = imagecreatefromstring($imageBytes);

            $this->assertSame(1200, imagesx($decodedImage));
            $this->assertSame(900, imagesy($decodedImage));
            $this->assertSame('glm-ocr', $request['model']);

            imagedestroy($decodedImage);

            return true;
        });

        $this->assertNotEmpty($service->extractFromDataUrl($dataUrl));
    }
    public function test_it_normalizes_string_authors_from_json_metadata(): void
    {
        $service = new BookCoverOcrService(Mockery::mock(OllamaService::class));

        $metadata = $service->metadataFromResponse([
            'response' => json_encode([
                'title' => 'MENUMBUHKAN KESADARAN BERAMAL',
                'authors' => 'Abdul Kholiq & Bayu Issetyadi',
            ]),
        ]);

        $this->assertSame(['Abdul Kholiq', 'Bayu Issetyadi'], $metadata['authors']);
    }
}
