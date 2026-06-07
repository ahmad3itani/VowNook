<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Non-table objects on the reception floor plan — dance floor, bar, DJ booth,
 * stage, and so on. Position and size are percentages of the room.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seating_elements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('other');
            $table->string('label')->nullable();
            $table->unsignedTinyInteger('position_x')->default(40);
            $table->unsignedTinyInteger('position_y')->default(40);
            $table->unsignedTinyInteger('width')->default(20);
            $table->unsignedTinyInteger('height')->default(15);
            $table->unsignedSmallInteger('rotation')->default(0);
            $table->timestamps();

            $table->index('wedding_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seating_elements');
    }
};
