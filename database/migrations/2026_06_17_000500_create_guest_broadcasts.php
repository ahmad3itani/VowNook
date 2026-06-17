<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guest broadcasts — one-off announcements the couple emails to a chosen
 * audience (everyone / attending / not yet replied / maybe). Each send is
 * recorded for a sent-history list.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_broadcasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->string('subject');
            $table->text('body');
            $table->string('audience')->default('all'); // all|attending|pending|maybe
            $table->unsignedInteger('recipient_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('wedding_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_broadcasts');
    }
};
