<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reception tables for the seating chart. position_x / position_y are
 * percentage offsets (0–100) on the floor-plan canvas so the layout scales
 * with the viewport.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seating_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('shape')->default('round');
            $table->unsignedInteger('capacity')->default(8);
            $table->unsignedTinyInteger('position_x')->default(40);
            $table->unsignedTinyInteger('position_y')->default(40);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('wedding_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seating_tables');
    }
};
