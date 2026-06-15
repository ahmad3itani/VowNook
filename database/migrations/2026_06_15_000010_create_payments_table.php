<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            // deposit | balance | refund
            $table->string('type', 20);
            $table->unsignedBigInteger('amount_cents');
            // Platform commission attributed to this payment (prorated share).
            $table->unsignedBigInteger('application_fee_cents')->default(0);
            // pending | succeeded | failed | refunded
            $table->string('status', 20)->default('pending')->index();
            $table->string('currency', 3)->default('cad');
            $table->string('stripe_session_id')->nullable()->index();
            $table->string('stripe_payment_intent_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
