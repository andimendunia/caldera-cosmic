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

        Schema::create('inv_currs', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->char('name', 3)->unique();
            $table->decimal('rate', 8, 2);
            $table->boolean('is_active')->default(true);
        });

        DB::table('inv_currs')->insert([
            [
                'name' => 'USD',
                'rate' => 1.00,
            ],
            [
                'name' => 'IDR',
                'rate' => 16290.00,
            ]
        ]);
        
        Schema::create('inv_areas', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name')->unique();
        });

        DB::table('inv_areas')->insert([
            [
                'name' => 'TT MM (DEMO)'
            ]
        ]);
        

        Schema::create('inv_auths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('inv_area_id')->constrained('inv_areas');
            $table->json('actions');
            $table->timestamps();
        });

        Schema::create('inv_locs', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('parent');
            $table->string('bin');
            $table->foreignId('inv_area_id')->constrained();
            $table->index('inv_area_id');
        });

        Schema::create('inv_items', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
            $table->string('desc');
            $table->string('code')->nullable();
            $table->foreignId('inv_loc_id')->nullable()->constrained();
            $table->foreignId('inv_area_id')->constrained();
            $table->string('photo');
            $table->boolean('is_active');
            $table->index('code');
            $table->index('inv_loc_id');
            $table->index('inv_area_id');
        });

        Schema::create('inv_stocks', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('inv_item_id')->constrained();
            $table->foreignId('inv_curr_id')->constrained();
            $table->unsignedInteger('qty')->default(0);
            $table->string('uom'); // ea, pcs
            $table->decimal('unit_price', 11, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->index('inv_item_id');
            $table->index('inv_curr_id');
        });

        Schema::create('inv_tags', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
        });

        Schema::create('inv_item_tags', function (Blueprint $table) {
            $table->foreignId('inv_item_id')->constrained();
            $table->foreignId('inv_tag_id')->constrained();
            $table->primary(['inv_item_id', 'inv_tag_id']);            
            $table->index('inv_item_id');
            $table->index('inv_tag_id');
        });

        Schema::create('inv_circs', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('user_id')->constrained();
            $table->enum('type', ['deposit', 'withdrawal', 'capture']);
            $table->enum('evaluation_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('evaluator_id')->constrained('users')->nullable();
            $table->string('evaluation_note')->nullable();
            $table->foreignId('inv_stock_id')->constrained();
            $table->integer('qty_relative');
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('unit_price', 15, 2);  // Added for historical price tracking
            $table->string('remarks')->nullable();
            $table->index('evaluator_id');
            $table->index('inv_stock_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'inv_circs',
            'inv_item_tags',
            'inv_tags',
            'inv_stocks',
            'inv_items',
            'inv_locs',
            'inv_auths',
            'inv_areas',
            'inv_currs',
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }
};
