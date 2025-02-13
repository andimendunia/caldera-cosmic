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
        Schema::dropIfExists('latihan_siswas');
        Schema::create('latihan_siswas', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->string('nama');
            $table->integer('umur');
            $table->enum('jk', ['male', 'female']);
            $table->foreignId('kelas_id');;
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('latihan_siswas');
    }
};
