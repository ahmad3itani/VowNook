<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Private photo gallery. Files live on the local (non-public) disk and are
 * served through an authenticated, tenancy-checked route so a wedding's photos
 * are never exposed by a guessable URL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gallery_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 100);
            $table->unsignedBigInteger('size');
            $table->string('caption')->nullable();
            $table->timestamps();

            $table->index('wedding_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gallery_photos');
    }
};
