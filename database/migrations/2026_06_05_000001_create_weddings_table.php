<?php

use App\Enums\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weddings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->date('event_date')->nullable();
            $table->string('timezone')->default('UTC');
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Membership pivot: which users belong to which wedding, with what role
        // and an optional per-user permission override (section => level).
        Schema::create('wedding_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_id')->constrained('weddings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default(Role::Collaborator->value);
            $table->json('permissions')->nullable();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->unique(['wedding_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wedding_user');
        Schema::dropIfExists('weddings');
    }
};
