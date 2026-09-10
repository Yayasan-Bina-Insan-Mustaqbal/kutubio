<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BorrowerEnrollment extends Model
{
    protected $fillable = ['borrower_id', 'academic_year', 'grade', 'section', 'class_name', 'gender', 'status', 'source_import_id'];

    public function borrower(): BelongsTo
    {
        return $this->belongsTo(Borrower::class);
    }

    public function sourceImport(): BelongsTo
    {
        return $this->belongsTo(BorrowerImport::class, 'source_import_id');
    }
}
