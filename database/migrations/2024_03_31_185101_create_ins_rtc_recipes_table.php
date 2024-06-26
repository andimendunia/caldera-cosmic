<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ins_rtc_recipes', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->string('name')->unique();
            $table->string('og_rs');
            $table->decimal('std_min', 4, 2);
            $table->decimal('std_max', 4, 2);
            $table->decimal('std_mid', 4, 2);
            $table->decimal('scale', 4, 2);
            $table->decimal('pfc_min', 4, 2);
            $table->decimal('pfc_max', 4, 2);

            $table->index('name');
        });

        DB::table('ins_rtc_recipes')->insert([
            [
                'ID' => 1,
                'NAME' => 'AF1 GS (BOTTOM)',
                'OG_RS' => 1,
                'STD_MIN' => 3,
                'STD_MAX' => '3.1',
                'STD_MID' => '3.05',
                'SCALE' => 1,
                'PFC_MIN' => '3.4',
                'PFC_MAX' => '3.6',
                ],
                [
                'ID' => 2,
                'NAME' => 'AF1 GS (ONE COLOR)',
                'OG_RS' => 1,
                'STD_MIN' => 3,
                'STD_MAX' => '3.1',
                'STD_MID' => '3.05',
                'SCALE' => 1,
                'PFC_MIN' => '3.4',
                'PFC_MAX' => '3.6',
                ],
                [
                'ID' => 3,
                'NAME' => 'AF1 GS (TWO COLOR)',
                'OG_RS' => 1,
                'STD_MIN' => 3,
                'STD_MAX' => '3.1',
                'STD_MID' => '3.05',
                'SCALE' => 1,
                'PFC_MIN' => '3.2',
                'PFC_MAX' => '3.4',
                ],
                [
                'ID' => 4,
                'NAME' => 'AF1 WS (BOTOM/CLEAR)',
                'OG_RS' => 13,
                'STD_MIN' => '3.6',
                'STD_MAX' => '3.8',
                'STD_MID' => '3.7',
                'SCALE' => 1,
                'PFC_MIN' => '3.6',
                'PFC_MAX' => '3.8',
                ],
                [
                'ID' => 5,
                'NAME' => 'AF1 WS (LOGO)',
                'OG_RS' => 7,
                'STD_MIN' => '0.6',
                'STD_MAX' => '0.8',
                'STD_MID' => '0.7',
                'SCALE' => 1,
                'PFC_MIN' => '0.6',
                'PFC_MAX' => '0.8',
                ],
                [
                'ID' => 6,
                'NAME' => 'AF1 WS (ONE COLOR)',
                'OG_RS' => 11,
                'STD_MIN' => 3,
                'STD_MAX' => '3.1',
                'STD_MID' => '3.05',
                'SCALE' => 1,
                'PFC_MIN' => '3.4',
                'PFC_MAX' => '3.6',
                ],
                [
                'ID' => 7,
                'NAME' => 'AF1 WS (TWO COLOR)',
                'OG_RS' => 7,
                'STD_MIN' => 3,
                'STD_MAX' => '3.1',
                'STD_MID' => '3.05',
                'SCALE' => 1,
                'PFC_MIN' => '3.4',
                'PFC_MAX' => '3.6',
                ],
                [
                'ID' => 8,
                'NAME' => 'AM 270 (CENTER)',
                'OG_RS' => 1,
                'STD_MIN' => '2.7',
                'STD_MAX' => '2.9',
                'STD_MID' => '2.8',
                'SCALE' => 1,
                'PFC_MIN' => '2.7',
                'PFC_MAX' => '2.9',
                ],
                [
                'ID' => 9,
                'NAME' => 'AM 270 (FOREFOOT)',
                'OG_RS' => 1,
                'STD_MIN' => 2,
                'STD_MAX' => '2.2',
                'STD_MID' => '2.1',
                'SCALE' => 1,
                'PFC_MIN' => 2,
                'PFC_MAX' => '2.2',
                ],
                [
                'ID' => 10,
                'NAME' => 'AM 270 (HEEL)',
                'OG_RS' => 13,
                'STD_MIN' => '2.8',
                'STD_MAX' => 3,
                'STD_MID' => '2.9',
                'SCALE' => 1,
                'PFC_MIN' => '2.8',
                'PFC_MAX' => 3,
                ],
                [
                'ID' => 11,
                'NAME' => 'AM 270 (TOE)',
                'OG_RS' => 1,
                'STD_MIN' => '2.7',
                'STD_MAX' => '2.8',
                'STD_MID' => '2.75',
                'SCALE' => 1,
                'PFC_MIN' => '2.7',
                'PFC_MAX' => '2.9',
                ],
                [
                'ID' => 12,
                'NAME' => 'AM 95 (FF)',
                'OG_RS' => 1,
                'STD_MIN' => '2.7',
                'STD_MAX' => '2.8',
                'STD_MID' => '2.75',
                'SCALE' => 1,
                'PFC_MIN' => '2.8',
                'PFC_MAX' => 3,
                ],
                [
                'ID' => 13,
                'NAME' => 'AM 95 (HEEL)',
                'OG_RS' => 1,
                'STD_MIN' => '2.8',
                'STD_MAX' => 3,
                'STD_MID' => '2.9',
                'SCALE' => 1,
                'PFC_MIN' => '2.8',
                'PFC_MAX' => 3,
                ],
                [
                'ID' => 14,
                'NAME' => 'AM 95 (LOGO)',
                'OG_RS' => 28,
                'STD_MIN' => '0.6',
                'STD_MAX' => '0.8',
                'STD_MID' => '0.7',
                'SCALE' => 1,
                'PFC_MIN' => '0.6',
                'PFC_MAX' => '0.8',
                ],
                [
                'ID' => 15,
                'NAME' => 'AM 95 (ONE COLOR)',
                'OG_RS' => 1,
                'STD_MIN' => '2.8',
                'STD_MAX' => 3,
                'STD_MID' => '2.9',
                'SCALE' => 1,
                'PFC_MIN' => '2.8',
                'PFC_MAX' => 3,
                ],
                [
                'ID' => 16,
                'NAME' => 'AM TW (CENTER)',
                'OG_RS' => 1,
                'STD_MIN' => '3.4',
                'STD_MAX' => '3.6',
                'STD_MID' => '3.5',
                'SCALE' => 1,
                'PFC_MIN' => '3.4',
                'PFC_MAX' => '3.6',
                ],
                [
                'ID' => 17,
                'NAME' => 'AM TW (HEEL POD)',
                'OG_RS' => 1,
                'STD_MIN' => '5.5',
                'STD_MAX' => '5.7',
                'STD_MID' => '5.6',
                'SCALE' => 1,
                'PFC_MIN' => '5.5',
                'PFC_MAX' => '5.7',
                ],
                [
                'ID' => 18,
                'NAME' => 'AM TW (ONE COLOR)',
                'OG_RS' => 5,
                'STD_MIN' => '3.2',
                'STD_MAX' => '3.3',
                'STD_MID' => '3.25',
                'SCALE' => 1,
                'PFC_MIN' => '3.4',
                'PFC_MAX' => '3.6',
                ],
                [
                'ID' => 19,
                'NAME' => 'AM TW (TWO COLOR)',
                'OG_RS' => 5,
                'STD_MIN' => '3.2',
                'STD_MAX' => '3.3',
                'STD_MID' => '3.25',
                'SCALE' => 1,
                'PFC_MIN' => '3.4',
                'PFC_MAX' => '3.6',
                ],
                [
                'ID' => 20,
                'NAME' => 'ALPHA 4',
                'OG_RS' => 1,
                'STD_MIN' => '2.6',
                'STD_MAX' => '2.8',
                'STD_MID' => '2.7',
                'SCALE' => 1,
                'PFC_MIN' => '2.6',
                'PFC_MAX' => '2.8',
                ],
                [
                'ID' => 21,
                'NAME' => 'ALPHA 5',
                'OG_RS' => 1,
                'STD_MIN' => '3.2',
                'STD_MAX' => '3.4',
                'STD_MID' => '3.3',
                'SCALE' => 1,
                'PFC_MIN' => '3.2',
                'PFC_MAX' => '3.4',
                ],
                [
                'ID' => 22,
                'NAME' => 'ALPHA 5 (MARBLE)',
                'OG_RS' => 1,
                'STD_MIN' => '3.2',
                'STD_MAX' => '3.4',
                'STD_MID' => '3.3',
                'SCALE' => 1,
                'PFC_MIN' => '3.2',
                'PFC_MAX' => '3.4',
                ],
                [
                'ID' => 23,
                'NAME' => 'ALPHA 6',
                'OG_RS' => 1,
                'STD_MIN' => '2.7',
                'STD_MAX' => '2.7',
                'STD_MID' => '2.7',
                'SCALE' => 1,
                'PFC_MIN' => '2.8',
                'PFC_MAX' => 3,
                ],
                [
                'ID' => 24,
                'NAME' => 'CBR (BOTTOM)',
                'OG_RS' => 1,
                'STD_MIN' => '3.6',
                'STD_MAX' => '3.7',
                'STD_MID' => '3.65',
                'SCALE' => 1,
                'PFC_MIN' => '3.8',
                'PFC_MAX' => 4,
                ],
                [
                'ID' => 25,
                'NAME' => 'CBR (ONE COLOR)',
                'OG_RS' => 1,
                'STD_MIN' => '3.3',
                'STD_MAX' => '3.3',
                'STD_MID' => '3.3',
                'SCALE' => 1,
                'PFC_MIN' => '3.4',
                'PFC_MAX' => '3.6',
                ],
                [
                'ID' => 26,
                'NAME' => 'CBR (TWO COLOR)',
                'OG_RS' => 1,
                'STD_MIN' => '3.3',
                'STD_MAX' => '3.3',
                'STD_MID' => '3.3',
                'SCALE' => 1,
                'PFC_MIN' => '3.4',
                'PFC_MAX' => '3.6',
                ],
                [
                'ID' => 27,
                'NAME' => 'DWS 11',
                'OG_RS' => 11,
                'STD_MIN' => '2.3',
                'STD_MAX' => '2.3',
                'STD_MID' => '2.3',
                'SCALE' => 1,
                'PFC_MIN' => '2.4',
                'PFC_MAX' => '2.6',
                ],
                [
                'ID' => 28,
                'NAME' => 'DWS 12 (ONE COLOR)',
                'OG_RS' => 5,
                'STD_MIN' => '2.1',
                'STD_MAX' => '2.2',
                'STD_MID' => '2.15',
                'SCALE' => 1,
                'PFC_MIN' => '2.2',
                'PFC_MAX' => '2.4',
                ],
                [
                'ID' => 29,
                'NAME' => 'DWS 12 (TWO COLOR FF)',
                'OG_RS' => 5,
                'STD_MIN' => '2.1',
                'STD_MAX' => '2.2',
                'STD_MID' => '2.15',
                'SCALE' => 1,
                'PFC_MIN' => '2.2',
                'PFC_MAX' => '2.4',
                ],
                [
                'ID' => 30,
                'NAME' => 'DWS 12 (TWO COLOR HEEL)',
                'OG_RS' => 5,
                'STD_MIN' => '2.6',
                'STD_MAX' => '2.8',
                'STD_MID' => '2.7',
                'SCALE' => 1,
                'PFC_MIN' => '2.6',
                'PFC_MAX' => '2.8',
                ],
                [
                'ID' => 31,
                'NAME' => 'DWS 13',
                'OG_RS' => 5,
                'STD_MIN' => '1.9',
                'STD_MAX' => 2,
                'STD_MID' => '1.95',
                'SCALE' => 1,
                'PFC_MIN' => 2,
                'PFC_MAX' => '2.2',
                ],
                [
                'ID' => 32,
                'NAME' => 'INVIGOR',
                'OG_RS' => 1,
                'STD_MIN' => '4.1',
                'STD_MAX' => '4.3',
                'STD_MID' => '4.2',
                'SCALE' => 1,
                'PFC_MIN' => 5,
                'PFC_MAX' => '5.2',
                ],
                [
                'ID' => 33,
                'NAME' => 'PEG 37/38 (LATERAL)',
                'OG_RS' => 2,
                'STD_MIN' => '2.8',
                'STD_MAX' => 3,
                'STD_MID' => '2.9',
                'SCALE' => 1,
                'PFC_MIN' => '2.8',
                'PFC_MAX' => 3,
                ],
                [
                'ID' => 34,
                'NAME' => 'PEG 37/38 (MEDIAL)',
                'OG_RS' => 1,
                'STD_MIN' => '2.4',
                'STD_MAX' => '2.6',
                'STD_MID' => '2.5',
                'SCALE' => 1,
                'PFC_MIN' => '2.4',
                'PFC_MAX' => '2.6',
                ],
                [
                'ID' => 35,
                'NAME' => 'PEG 39/40 (LETERAL)',
                'OG_RS' => 45,
                'STD_MIN' => '2.3',
                'STD_MAX' => '2.5',
                'STD_MID' => '2.4',
                'SCALE' => 1,
                'PFC_MIN' => '2.3',
                'PFC_MAX' => '2.5',
                ],
                [
                'ID' => 36,
                'NAME' => 'PEG 39/40 (MEDIAL)',
                'OG_RS' => 45,
                'STD_MIN' => '2.1',
                'STD_MAX' => '2.3',
                'STD_MID' => '2.2',
                'SCALE' => 1,
                'PFC_MIN' => '2.1',
                'PFC_MAX' => '2.3',
                ],
                [
                'ID' => 37,
                'NAME' => 'PEG 39/40 (ONE COLOR)',
                'OG_RS' => 45,
                'STD_MIN' => '2.3',
                'STD_MAX' => '2.5',
                'STD_MID' => '2.4',
                'SCALE' => 1,
                'PFC_MIN' => '2.3',
                'PFC_MAX' => '2.5',
                ],
                [
                'ID' => 38,
                'NAME' => 'PEG 41 (LATERAL)',
                'OG_RS' => 48,
                'STD_MIN' => '2.4',
                'STD_MAX' => '2.5',
                'STD_MID' => '2.45',
                'SCALE' => 1,
                'PFC_MIN' => '2.6',
                'PFC_MAX' => '2.8',
                ],
                [
                'ID' => 39,
                'NAME' => 'PEG 41 (MEDIAL)',
                'OG_RS' => 48,
                'STD_MIN' => '2.4',
                'STD_MAX' => '2.5',
                'STD_MID' => '2.45',
                'SCALE' => 1,
                'PFC_MIN' => '2.6',
                'PFC_MAX' => '2.8',
                ],
                [
                'ID' => 40,
                'NAME' => 'PHOENIX WAFFLE',
                'OG_RS' => 1,
                'STD_MIN' => '2.7',
                'STD_MAX' => '2.7',
                'STD_MID' => '2.7',
                'SCALE' => 1,
                'PFC_MIN' => '2.8',
                'PFC_MAX' => 3,
                ],
                [
                'ID' => 41,
                'NAME' => 'QUEST 6',
                'OG_RS' => 5,
                'STD_MIN' => '2.3',
                'STD_MAX' => '2.3',
                'STD_MID' => '2.3',
                'SCALE' => 1,
                'PFC_MIN' => '2.4',
                'PFC_MAX' => '2.6',
                ],
                [
                'ID' => 42,
                'NAME' => 'ALPHA 8 (FOREFOOT)',
                'OG_RS' => 11,
                'STD_MIN' => '2.6',
                'STD_MAX' => '2.7',
                'STD_MID' => '2.65',
                'SCALE' => 1,
                'PFC_MIN' => '2.8',
                'PFC_MAX' => 3,
                ],
                [
                'ID' => 43,
                'NAME' => 'ALPHA 8 (HEEL)',
                'OG_RS' => 11,
                'STD_MIN' => '3.2',
                'STD_MAX' => '3.3',
                'STD_MID' => '3.25',
                'SCALE' => 1,
                'PFC_MIN' => '3.3',
                'PFC_MAX' => '3.5',
                ],                
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ins_rtc_recipes');
    }
};
