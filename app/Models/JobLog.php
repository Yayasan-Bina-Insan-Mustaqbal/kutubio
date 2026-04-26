<?php

namespace App\Models;

use App\Enums\JobLogStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['capture_session_id', 'job_uuid', 'queue_job_id', 'connection', 'queue', 'display_name', 'status', 'attempts', 'payload', 'exception', 'queued_at', 'started_at', 'finished_at'])]
class JobLog extends Model
{
    protected $attributes = [
        'status' => JobLogStatus::Queued->value,
        'attempts' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'capture_session_id' => 'integer',
            'status' => JobLogStatus::class,
            'attempts' => 'integer',
            'payload' => 'array',
            'queued_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<CaptureSession, $this>
     */
    public function captureSession(): BelongsTo
    {
        return $this->belongsTo(CaptureSession::class);
    }
}
