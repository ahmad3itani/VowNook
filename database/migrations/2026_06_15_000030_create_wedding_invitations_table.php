<?php

use App\Enums\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wedding_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_id')->constrained('weddings')->cascadeOnDelete();
            $table->string('email');
            $table->string('role')->default(Role::Collaborator->value);
            // Sparse per-section override (section => level) vs the role defaults.
            $table->json('permissions')->nullable();
            $table->string('token', 64)->unique();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            // At most one pending invite per email per wedding.
            $table->unique(['wedding_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wedding_invitations');
    }
};
