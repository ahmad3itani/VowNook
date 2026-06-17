<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-event celebrations (rehearsal dinner, welcome party, ceremony, brunch …)
 * with a per-event guest RSVP pivot. The couple's overall reply stays on
 * guests.rsvp_status (back-compat for seating/dashboard); these are extra
 * gatherings guests can reply to individually.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wedding_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('other'); // ceremony|reception|rehearsal|welcome|brunch|other
            $table->date('event_date')->nullable();
            $table->string('start_time')->nullable();
            $table->string('end_time')->nullable();
            $table->string('venue_name')->nullable();
            $table->string('address')->nullable();
            $table->string('dress_code')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_rsvpable')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('wedding_id');
        });

        Schema::create('event_guest', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->constrained()->cascadeOnDelete();
            $table->string('rsvp_status')->default('pending'); // pending|attending|declined
            $table->timestamps();

            $table->unique(['wedding_event_id', 'guest_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_guest');
        Schema::dropIfExists('wedding_events');
    }
};
