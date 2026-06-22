<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Richer wedding-website sections: a wedding party, an FAQ, a local "things to
 * do" guide, and a moderated guestbook. FAQ + local guide are text-only so they
 * live as JSON on the website; the party (photos) and guestbook (guest-created,
 * moderated over time) get their own tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wedding_websites', function (Blueprint $table) {
            $table->json('faq_items')->nullable()->after('travel_notes');            // [{question, answer}]
            $table->json('local_recommendations')->nullable()->after('faq_items');   // [{title, category, description, url}]
        });

        Schema::create('wedding_party_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('role')->nullable();       // Maid of Honour, Best Man, …
            $table->string('side')->default('other'); // partner_a | partner_b | family | other
            $table->text('bio')->nullable();
            $table->string('photo_path')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('wedding_id');
        });

        Schema::create('guestbook_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('message');
            $table->timestamp('approved_at')->nullable(); // null = awaiting moderation
            $table->timestamps();

            $table->index(['wedding_id', 'approved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guestbook_entries');
        Schema::dropIfExists('wedding_party_members');

        Schema::table('wedding_websites', function (Blueprint $table) {
            $table->dropColumn(['faq_items', 'local_recommendations']);
        });
    }
};
