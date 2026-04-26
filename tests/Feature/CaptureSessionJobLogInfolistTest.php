<?php

namespace Tests\Feature;

use App\Enums\CaptureSessionStatus;
use App\Enums\JobLogStatus;
use App\Filament\Resources\CaptureSessions\CaptureSessionResource;
use App\Filament\Resources\CaptureSessions\Pages\ViewCaptureSession;
use App\Jobs\ExtractBookDataWithVisionJob;
use App\Models\CaptureSession;
use App\Models\JobLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CaptureSessionJobLogInfolistTest extends TestCase
{
    use RefreshDatabase;

    public function test_capture_session_view_shows_related_job_logs(): void
    {
        $this->actingAs(User::factory()->create());

        $captureSession = CaptureSession::factory()->create([
            'status' => CaptureSessionStatus::Captured,
            'front_image_path' => null,
            'back_image_path' => null,
        ]);

        JobLog::create([
            'capture_session_id' => $captureSession->id,
            'job_uuid' => '3d9bdf10-9dd8-439a-a2e3-e2bb7ec5ed1c',
            'queue_job_id' => '12',
            'connection' => 'database',
            'queue' => 'default',
            'display_name' => ExtractBookDataWithVisionJob::class,
            'status' => JobLogStatus::Succeed,
            'attempts' => 1,
            'queued_at' => now()->subMinute(),
            'started_at' => now()->subSeconds(45),
            'finished_at' => now(),
        ]);

        $this->get(CaptureSessionResource::getUrl('view', ['record' => $captureSession]))
            ->assertOk()
            ->assertSee('Job log')
            ->assertSee('ExtractBookDataWithVisionJob')
            ->assertSee('Succeed');
    }

    public function test_ai_processing_actions_can_be_retriggered_while_session_is_processing(): void
    {
        $this->actingAs(User::factory()->create());

        $captureSession = CaptureSession::factory()->create([
            'status' => CaptureSessionStatus::Processing,
            'front_image_path' => 'capture-sessions/front.jpg',
            'back_image_path' => 'capture-sessions/back.jpg',
        ]);

        Livewire::test(ViewCaptureSession::class, ['record' => $captureSession->id])
            ->assertInfolistActionEnabled('processingActions', 'extractTitle')
            ->assertInfolistActionEnabled('processingActions', 'readIsbnQr')
            ->assertInfolistActionEnabled('processingActions', 'summarize')
            ->assertInfolistActionEnabled('processingActions', 'processAll');
    }
}
