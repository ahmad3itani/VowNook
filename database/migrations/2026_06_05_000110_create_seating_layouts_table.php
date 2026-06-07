<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-wedding floor-plan settings. Room dimensions (in feet) drive the canvas
 * aspect ratio; tables and elements are positioned by percentage within it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seating_layouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('room_width')->default(40);
            $table->unsignedInteger('room_height')->default(30);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seating_layouts');
    }
};
