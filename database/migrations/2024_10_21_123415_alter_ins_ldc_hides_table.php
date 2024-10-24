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
        Schema::table('ins_ldc_hides', function (Blueprint $table) {
            $table->unsignedTinyInteger('machine')->nullable()->after('grade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ins_ldc_hides', function (Blueprint $table) {
            $table->dropColumn('machine'); // Remove 'data' column if rolled back
        });
    }
};