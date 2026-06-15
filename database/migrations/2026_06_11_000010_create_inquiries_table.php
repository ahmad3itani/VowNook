<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->foreignId('couple_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('vendor_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_service_id')->nullable()->constrained()->nullOnDelete();
            $table->date('event_date')->nullable();
            $table->unsignedSmallInteger('guest_count')->nullable();
            $table->unsignedBigInteger('budget_cents')->nullable();
            $table->text('message');
            $table->string('status', 20)->default('requested')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiries');
    }
};
