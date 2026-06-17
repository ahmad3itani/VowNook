<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Travel & stays — hotel room blocks, rentals, and transport options the couple
 * recommends to guests, plus a free-text travel notes field on the website
 * (parking, shuttle, getting there).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wedding_accommodations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('hotel'); // hotel|rental|transport
            $table->string('address')->nullable();
            $table->text('blurb')->nullable();
            $table->string('booking_url')->nullable();
            $table->string('block_code')->nullable();   // group rate / block code
            $table->string('price_note')->nullable();    // "from $159/night"
            $table->string('distance_note')->nullable(); // "5 min from venue"
            $table->string('image_path')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('wedding_id');
        });

        Schema::table('wedding_websites', function (Blueprint $table) {
            $table->text('travel_notes')->nullable()->after('music_title');
        });
    }

    public function down(): void
    {
        Schema::table('wedding_websites', function (Blueprint $table) {
            $table->dropColumn('travel_notes');
        });

        Schema::dropIfExists('wedding_accommodations');
    }
};
