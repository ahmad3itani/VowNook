<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inspiration / mood-board items. Each pin is a saved idea — an image and/or a
 * link — grouped by theme so the couple can collect a visual vision in one place.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspiration_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('category')->default('other');
            $table->string('image_url')->nullable();
            $table->string('link_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['wedding_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspiration_items');
    }
};
