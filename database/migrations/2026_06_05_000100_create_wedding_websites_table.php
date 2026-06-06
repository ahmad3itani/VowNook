<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Content for a couple's public wedding website (one row per wedding). The site
 * is only reachable publicly once `is_published` is set.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wedding_websites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('is_published')->default(false);
            $table->string('headline')->nullable();
            $table->text('welcome_message')->nullable();
            $table->text('our_story')->nullable();
            $table->string('venue_name')->nullable();
            $table->string('venue_address')->nullable();
            $table->string('ceremony_time')->nullable();
            $table->string('dress_code')->nullable();
            $table->string('hero_image_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wedding_websites');
    }
};
