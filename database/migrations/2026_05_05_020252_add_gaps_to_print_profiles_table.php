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
        Schema::table('print_profiles', function (Blueprint $table) {
            $table->decimal('gap_x_mm', 8, 2)->default(0)->after('slot_height_mm');
            $table->decimal('gap_y_mm', 8, 2)->default(0)->after('gap_x_mm');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('print_profiles', function (Blueprint $table) {
            $table->dropColumn(['gap_x_mm', 'gap_y_mm']);
        });
    }
};
