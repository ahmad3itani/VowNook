<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Day-of schedule. Each entry may optionally reference a vendor so the
 * timeline ties the vendor directory to the run-of-show.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timeline_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('type')->default('other');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['wedding_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timeline_events');
    }
};
