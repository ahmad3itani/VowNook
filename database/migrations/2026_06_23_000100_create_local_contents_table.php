<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stored, AI-generated unique copy for the programmatic local-SEO pages — the
 * category hubs (city_slug null) and the city x category pages. Generated once
 * and stored (NOT regenerated per request) so the content is stable for
 * crawlers and turns thin listing pages into genuinely useful local guides.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('local_contents', function (Blueprint $table) {
            $table->id();
            $table->string('category');            // VendorCategory value
            $table->string('city_slug')->nullable(); // null = the Ontario hub
            $table->text('intro')->nullable();      // markdown local-guide copy
            $table->json('faqs')->nullable();       // [{question, answer}]
            $table->timestamps();

            $table->unique(['category', 'city_slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('local_contents');
    }
};
