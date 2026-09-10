<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BorrowerImport extends Model
{
    protected $fillable = ['source_type', 'source_url', 'sheet_id', 'gid', 'academic_year', 'content_hash', 'status', 'created_by', 'fetched_at', 'committed_at'];

    protected function casts(): array
    {
        return ['fetched_at' => 'datetime', 'committed_at' => 'datetime'];
    }

    public function rows(): HasMany
    {
        return $this->hasMany(BorrowerImportRow::class);
    }

}
