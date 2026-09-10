<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borrower_imports', function (Blueprint $table): void {
            $table->id();
            $table->string('source_type')->default('google_sheet');
            $table->text('source_url');
            $table->string('sheet_id')->nullable();
            $table->string('gid')->nullable();
            $table->string('academic_year', 20);
            $table->string('content_hash', 64);
            $table->string('status')->default('preview');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('fetched_at');
            $table->timestamp('committed_at')->nullable();
            $table->timestamps();
            $table->index(['academic_year', 'status']);
            $table->unique(['content_hash', 'academic_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrower_imports');
    }
};
