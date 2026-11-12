<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('borrower_import_rows', function (Blueprint $table): void {
            $table->json('normalized_data')->nullable()->after('raw_data');
        });
    }

    public function down(): void
    {
        Schema::table('borrower_import_rows', function (Blueprint $table): void {
            $table->dropColumn('normalized_data');
        });
    }
};
