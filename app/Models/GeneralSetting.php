<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneralSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'library_name',
        'library_address',
        'library_employee_name',
        'default_loan_duration_days',
        'max_books_per_borrower',
        'fine_per_day',
    ];

    protected $casts = [
        'fine_per_day' => 'decimal:2',
    ];
}
