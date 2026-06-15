<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('business_name');
            $table->string('slug')->unique();
            $table->string('category', 40)->index();
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('cover_path')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->string('country', 2)->nullable();
            $table->string('service_area')->nullable();
            $table->unsignedBigInteger('base_price_cents')->nullable();
            $table->string('price_unit', 20)->nullable(); // per_event|per_hour|per_person
            $table->string('website')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('email')->nullable();
            $table->json('socials')->nullable();
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);
            $table->string('status', 20)->default('draft')->index();
            $table->string('stripe_account_id')->nullable();
            $table->boolean('is_accepting_bookings')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_profiles');
    }
};
