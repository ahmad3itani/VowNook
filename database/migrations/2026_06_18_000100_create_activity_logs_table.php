<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Platform-wide audit trail. Records admin actions (impersonation, plan changes,
 * suspensions, moderation) and key user events (login, signup, bookings) so the
 * oversight console can answer "who did what, when". `actor_id` is the user who
 * performed the action (null for system events); the optional polymorphic
 * subject is what it acted on.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action')->index();          // e.g. auth.login, admin.user.suspend
            $table->nullableMorphs('subject');           // subject_type + subject_id
            $table->text('description')->nullable();
            $table->json('properties')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
