<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BorrowerImportRow extends Model
{
    protected $fillable = ['borrower_import_id', 'row_number', 'raw_data', 'normalized_name', 'grade', 'section', 'matched_borrower_id', 'match_method', 'confidence', 'resolution_status', 'error_message'];

    protected function casts(): array
    {
        return ['raw_data' => 'array', 'confidence' => 'decimal:4'];
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(BorrowerImport::class, 'borrower_import_id');
    }

    public function borrower(): BelongsTo
    {
        return $this->belongsTo(Borrower::class, 'matched_borrower_id');
    }
}
