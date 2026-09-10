<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('book_copies')
            ->where('purchase_year', '3')
            ->whereYear('acquired_at', 2026)
            ->update(['purchase_year' => '2026']);
    }

    public function down(): void
    {
        DB::table('book_copies')
            ->where('purchase_year', '2026')
            ->update(['purchase_year' => '3']);
    }
};
