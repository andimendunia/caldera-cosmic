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
        Schema::create('ins_omv_captures', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->string('file_name');
            $table->foreignId('ins_omv_metric_id')->nullable()->constrained()->nullOnDelete(); // should be indexed
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ins_omv_captures');
    }
};
