<?php

namespace App\Support;

use App\Enums\JobLogStatus;
use App\Models\CaptureSession;
use App\Models\JobLog;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobQueued;
use JsonException;

class JobLogRecorder
{
    public function recordQueued(JobQueued $event): void
    {
        $payload = $event->payload();
        $jobUuid = $payload['uuid'] ?? null;

        if (! is_string($jobUuid) || $jobUuid === '') {
            return;
        }

        $captureSessionId = $this->captureSessionIdFromPayloadOrJob($payload, $event->job);
        $attributes = [
            'queue_job_id' => $event->id === null ? null : (string) $event->id,
            'connection' => $event->connectionName,
            'queue' => $event->queue,
            'display_name' => $this->displayName($payload),
            'status' => JobLogStatus::Queued,
            'attempts' => 0,
            'payload' => $payload,
            'exception' => null,
            'queued_at' => now(),
            'started_at' => null,
            'finished_at' => null,
        ];

        if ($captureSessionId !== null) {
            $attributes['capture_session_id'] = $captureSessionId;
        }

        JobLog::updateOrCreate(['job_uuid' => $jobUuid], $attributes);
    }

    public function recordProcessing(JobProcessing $event): void
    {
        $payload = $this->payloadFromJob($event->job);
        $jobUuid = $event->job->uuid();

        if (! is_string($jobUuid) || $jobUuid === '') {
            return;
        }

        $captureSessionId = $this->captureSessionIdFromPayloadOrJob($payload);
        $attributes = [
            'queue_job_id' => $event->job->getJobId() === null ? null : (string) $event->job->getJobId(),
            'connection' => $event->connectionName,
            'queue' => $event->job->getQueue(),
            'display_name' => $this->displayName($payload),
            'status' => JobLogStatus::Ongoing,
            'attempts' => $event->job->attempts(),
            'payload' => $payload,
            'started_at' => now(),
        ];

        if ($captureSessionId !== null) {
            $attributes['capture_session_id'] = $captureSessionId;
        }

        JobLog::updateOrCreate(['job_uuid' => $jobUuid], $attributes);
    }

    public function recordProcessed(JobProcessed $event): void
    {
        $this->finishJob($event->job, JobLogStatus::Succeed);
    }

    public function recordFailed(JobFailed $event): void
    {
        $this->finishJob($event->job, JobLogStatus::Failed, (string) $event->exception);
    }

    private function finishJob(Job $job, JobLogStatus $status, ?string $exception = null): void
    {
        $payload = $this->payloadFromJob($job);
        $jobUuid = $job->uuid();

        if (! is_string($jobUuid) || $jobUuid === '') {
            return;
        }

        $captureSessionId = $this->captureSessionIdFromPayloadOrJob($payload);
        $attributes = [
            'queue_job_id' => $job->getJobId() === null ? null : (string) $job->getJobId(),
            'queue' => $job->getQueue(),
            'display_name' => $this->displayName($payload),
            'status' => $status,
            'attempts' => $job->attempts(),
            'payload' => $payload,
            'exception' => $exception,
            'finished_at' => now(),
        ];

        if ($captureSessionId !== null) {
            $attributes['capture_session_id'] = $captureSessionId;
        }

        JobLog::updateOrCreate(['job_uuid' => $jobUuid], $attributes);
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadFromJob(Job $job): array
    {
        try {
            return $job->payload();
        } catch (JsonException) {
            return [];
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function displayName(array $payload): string
    {
        $displayName = $payload['displayName'] ?? $payload['job'] ?? null;

        return is_string($displayName) && $displayName !== '' ? $displayName : 'Unknown Job';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function captureSessionIdFromPayloadOrJob(array $payload, mixed $job = null): ?int
    {
        $captureSessionId = $this->captureSessionIdFromJob($job);

        if ($captureSessionId !== null) {
            return $captureSessionId;
        }

        $command = $payload['data']['command'] ?? null;

        if (! is_string($command) || $command === '') {
            return null;
        }

        try {
            return $this->captureSessionIdFromJob(unserialize($command));
        } catch (\Throwable) {
            return null;
        }
    }

    private function captureSessionIdFromJob(mixed $job): ?int
    {
        if (! is_object($job) || ! property_exists($job, 'captureSession')) {
            return null;
        }

        $captureSession = $job->captureSession;

        return $captureSession instanceof CaptureSession ? $captureSession->getKey() : null;
    }
}
