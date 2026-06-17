<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Save-the-date / invitation sends with open-tracking. Each guest gets one row
 * per kind, carrying a unique token embedded in a 1x1 tracking pixel; opening
 * the email flips opened_at.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_sends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->constrained()->cascadeOnDelete();
            $table->string('kind'); // save_the_date|invitation
            $table->string('token', 64)->unique();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamps();

            $table->unique(['guest_id', 'kind']);
            $table->index(['wedding_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_sends');
    }
};
