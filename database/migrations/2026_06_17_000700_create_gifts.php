<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gifts received + thank-you tracking. Registry fund contributions auto-create a
 * gift row; couples add physical/cash gifts manually. Each row tracks whether a
 * thank-you note has been sent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('registry_contribution_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_name')->nullable();
            $table->string('kind')->default('physical'); // fund|item|cash|physical
            $table->unsignedBigInteger('amount_cents')->nullable();
            $table->date('received_at')->nullable();
            $table->boolean('thank_you_sent')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('wedding_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gifts');
    }
};
