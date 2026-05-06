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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('staff'); // admin, staff
        });

        Schema::table('books', function (Blueprint $table) {
            $table->softDeletes();
            $table->string('deletion_reason')->nullable();
        });

        Schema::table('book_copies', function (Blueprint $table) {
            $table->softDeletes();
            $table->string('deletion_reason')->nullable();
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->softDeletes();
            $table->string('deletion_reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        Schema::table('books', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn('deletion_reason');
        });

        Schema::table('book_copies', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn('deletion_reason');
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn('deletion_reason');
        });
    }
};
