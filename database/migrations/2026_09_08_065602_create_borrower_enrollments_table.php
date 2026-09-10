<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borrower_enrollments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('borrower_id')->constrained()->cascadeOnDelete();
            $table->string('academic_year', 20);
            $table->unsignedSmallInteger('grade')->nullable();
            $table->string('section', 10)->nullable();
            $table->string('class_name')->nullable();
            $table->string('gender')->nullable();
            $table->string('status')->default('active');
            $table->foreignId('source_import_id')->nullable()->constrained('borrower_imports')->nullOnDelete();
            $table->timestamps();
            $table->unique(['borrower_id', 'academic_year']);
            $table->index(['academic_year', 'grade', 'section']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrower_enrollments');
    }
};
