<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * VowNook Shop — digital stationery orders paid via hosted Stripe Checkout.
 * A pending row is written when checkout starts; the Stripe webhook marks it
 * fulfilled and sends the signed download link. Server-side only (no public
 * routes read this table directly; downloads go through a signed route).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_orders', function (Blueprint $table) {
            $table->id();
            $table->string('stripe_session_id')->nullable()->unique();
            $table->string('product_key');
            $table->string('product_name');
            $table->unsignedInteger('amount_cents');
            $table->string('currency', 8)->default('cad');
            $table->string('email')->nullable();
            $table->string('status')->default('pending'); // pending | fulfilled | refunded
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_orders');
    }
};
