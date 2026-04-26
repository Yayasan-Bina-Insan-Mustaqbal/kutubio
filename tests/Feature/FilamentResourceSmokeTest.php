<?php

namespace Tests\Feature;

use App\Filament\Resources\BookCopies\BookCopyResource;
use App\Filament\Resources\Books\BookResource;
use App\Filament\Resources\CaptureSessions\CaptureSessionResource;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Jobs\JobResource;
use App\Filament\Resources\MetadataRevisions\MetadataRevisionResource;
use App\Filament\Resources\PrintProfiles\PrintProfileResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FilamentResourceSmokeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: class-string}>
     */
    public static function resourceIndexProvider(): array
    {
        return [
            'books' => [BookResource::class],
            'book copies' => [BookCopyResource::class],
            'categories' => [CategoryResource::class],
            'capture sessions' => [CaptureSessionResource::class],
            'jobs' => [JobResource::class],
            'metadata revisions' => [MetadataRevisionResource::class],
            'print profiles' => [PrintProfileResource::class],
        ];
    }

    /**
     * @param  class-string  $resource
     */
    #[DataProvider('resourceIndexProvider')]
    public function test_authenticated_user_can_render_resource_index(string $resource): void
    {
        $this->actingAs(User::factory()->create());

        $this->get($resource::getUrl('index'))->assertOk();
    }
}
