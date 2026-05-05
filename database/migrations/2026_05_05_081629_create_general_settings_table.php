<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('general_settings', function (Blueprint $table) {
            $table->id();
            $table->string('library_name')->default('Perpustakaan SD Islam Insan Taqwa');
            $table->text('library_address')->nullable();
            $table->string('library_employee_name')->nullable();
            $table->integer('default_loan_duration_days')->default(7);
            $table->integer('max_books_per_borrower')->default(3);
            $table->decimal('fine_per_day', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('general_settings');
    }
};
