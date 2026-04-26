<?php

namespace Tests\Unit;

use App\Services\BookCoverOcrService;
use App\Services\OllamaService;
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
