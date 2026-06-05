<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->foreignId('group_id')->nullable()->constrained('guest_groups')->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('side')->default('both');
            $table->string('age_group')->default('adult');
            $table->boolean('is_plus_one')->default(false);
            $table->string('rsvp_status')->default('pending');
            $table->string('meal_choice')->nullable();
            $table->text('dietary_notes')->nullable();
            $table->timestamp('invited_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['wedding_id', 'rsvp_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};
