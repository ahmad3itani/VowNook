<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional albums (folders) for organising gallery photos. A photo with a null
 * album_id is "Unsorted". Deleting an album just un-sorts its photos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gallery_albums', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('wedding_id');
        });

        Schema::table('gallery_photos', function (Blueprint $table) {
            $table->foreignId('album_id')->nullable()->after('wedding_id')
                ->constrained('gallery_albums')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('gallery_photos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('album_id');
        });

        Schema::dropIfExists('gallery_albums');
    }
};
