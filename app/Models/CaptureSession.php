<?php

namespace App\Models;

use App\Enums\CaptureSessionStatus;
use App\Enums\QrParseStatus;
use Database\Factories\CaptureSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['submitted_by', 'status', 'current_processing_stage', 'quantity', 'front_image_path', 'back_image_path', 'front_image_meta', 'back_image_meta', 'decoded_qr_payload', 'qr_parse_status', 'failure_reason', 'submitted_at', 'processing_started_at', 'processing_finished_at'])]
class CaptureSession extends Model
{
    /** @use HasFactory<CaptureSessionFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => CaptureSessionStatus::PendingCapture->value,
    ];

    protected static function booted(): void
    {
        static::creating(function (CaptureSession $captureSession): void {
            $captureSession->public_id ??= (string) Str::ulid();
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * @return HasMany<MetadataRevision, $this>
     */
    public function metadataRevisions(): HasMany
    {
        return $this->hasMany(MetadataRevision::class);
    }

    /**
     * @return HasMany<JobLog, $this>
     */
    public function jobLogs(): HasMany
    {
        return $this->hasMany(JobLog::class)->latest();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'back_image_meta' => 'array',
            'front_image_meta' => 'array',
            'processing_finished_at' => 'datetime',
            'processing_started_at' => 'datetime',
            'qr_parse_status' => QrParseStatus::class,
            'status' => CaptureSessionStatus::class,
            'submitted_at' => 'datetime',
        ];
    }
}
