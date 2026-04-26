<?php

namespace Tests\Feature;

use App\Enums\JobLogStatus;
use App\Jobs\ExtractBookDataWithVisionJob;
use App\Models\CaptureSession;
use App\Models\JobLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class JobLogRecorderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_records_queue_job_lifecycle_in_one_log_row(): void
    {
        $payload = [
            'uuid' => '73b0d1c8-c5c3-4cd9-8758-6da690bfacdd',
            'displayName' => 'App\\Jobs\\ExtractBookDataWithVisionJob',
            'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
            'data' => [],
        ];

        Event::dispatch(new JobQueued(
            connectionName: 'database',
            queue: 'default',
            id: 123,
            job: 'App\\Jobs\\ExtractBookDataWithVisionJob',
            payload: json_encode($payload, JSON_THROW_ON_ERROR),
            delay: null,
        ));

        $this->assertSame(JobLogStatus::Queued, JobLog::firstOrFail()->status);

        $job = new SyncJob(
            container: app(),
            payload: json_encode($payload, JSON_THROW_ON_ERROR),
            connectionName: 'database',
            queue: 'default',
        );

        Event::dispatch(new JobProcessing('database', $job));
        $this->assertSame(JobLogStatus::Ongoing, JobLog::firstOrFail()->status);

        Event::dispatch(new JobProcessed('database', $job));

        $jobLog = JobLog::firstOrFail();

        $this->assertSame(JobLogStatus::Succeed, $jobLog->status);
        $this->assertSame('App\\Jobs\\ExtractBookDataWithVisionJob', $jobLog->display_name);
        $this->assertNotNull($jobLog->queued_at);
        $this->assertNotNull($jobLog->started_at);
        $this->assertNotNull($jobLog->finished_at);
        $this->assertSame(1, JobLog::count());
    }

    public function test_it_links_capture_session_jobs_to_their_capture_session(): void
    {
        $captureSession = CaptureSession::factory()->create();
        $queuedJob = new ExtractBookDataWithVisionJob($captureSession);
        $payload = [
            'uuid' => '426281f5-bec4-47a6-a937-3ceddf123980',
            'displayName' => ExtractBookDataWithVisionJob::class,
            'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
            'data' => [
                'commandName' => ExtractBookDataWithVisionJob::class,
                'command' => serialize($queuedJob),
            ],
        ];

        Event::dispatch(new JobQueued(
            connectionName: 'database',
            queue: 'default',
            id: 456,
            job: $queuedJob,
            payload: json_encode($payload, JSON_THROW_ON_ERROR),
            delay: null,
        ));

        $job = new SyncJob(
            container: app(),
            payload: json_encode($payload, JSON_THROW_ON_ERROR),
            connectionName: 'database',
            queue: 'default',
        );

        Event::dispatch(new JobProcessing('database', $job));
        Event::dispatch(new JobProcessed('database', $job));

        $jobLog = JobLog::firstOrFail();

        $this->assertSame($captureSession->id, $jobLog->capture_session_id);
        $this->assertTrue($captureSession->jobLogs()->whereKey($jobLog)->exists());
    }

    public function test_it_records_failed_queue_jobs(): void
    {
        $payload = [
            'uuid' => 'ee74fa55-3ddf-41d3-9d29-747fb30377f6',
            'displayName' => 'App\\Jobs\\ReadIsbnQrCodeJob',
            'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
            'data' => [],
        ];

        $job = new SyncJob(
            container: app(),
            payload: json_encode($payload, JSON_THROW_ON_ERROR),
            connectionName: 'database',
            queue: 'default',
        );

        Event::dispatch(new JobFailed('database', $job, new \RuntimeException('Could not read QR')));

        $jobLog = JobLog::firstOrFail();

        $this->assertSame(JobLogStatus::Failed, $jobLog->status);
        $this->assertStringContainsString('Could not read QR', $jobLog->exception);
        $this->assertNotNull($jobLog->finished_at);
    }
}
