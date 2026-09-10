<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borrower_import_rows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('borrower_import_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->json('raw_data');
            $table->string('normalized_name')->nullable()->index();
            $table->unsignedSmallInteger('grade')->nullable();
            $table->string('section', 10)->nullable();
            $table->foreignId('matched_borrower_id')->nullable()->constrained('borrowers')->nullOnDelete();
            $table->string('match_method')->nullable();
            $table->decimal('confidence', 5, 4)->nullable();
            $table->string('resolution_status')->default('needs_review');
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->unique(['borrower_import_id', 'row_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrower_import_rows');
    }
};
