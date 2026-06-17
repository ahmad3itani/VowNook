<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wedding gift registry: cash/honeymoon/custom funds (guests pay the couple
 * directly via the couple's own payout link — no platform-held funds) and a
 * gift-item registry (items with a store link guests can claim). Contributions
 * are logged for the progress bar + thank-you tracking.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registry_funds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('blurb')->nullable();
            $table->string('image_path')->nullable();
            $table->string('type')->default('cash'); // cash | honeymoon | custom
            $table->unsignedBigInteger('goal_cents')->nullable();
            $table->unsignedBigInteger('raised_cents')->default(0);
            $table->string('payout_url')->nullable(); // PayPal.me / Venmo / e-transfer / GoFundMe
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('wedding_id');
        });

        Schema::create('registry_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('blurb')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedBigInteger('price_cents')->nullable();
            $table->string('store_url')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('claimed_count')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('wedding_id');
        });

        Schema::create('registry_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registry_fund_id')->constrained()->cascadeOnDelete();
            $table->string('contributor_name')->nullable();
            $table->string('contributor_email')->nullable();
            $table->unsignedBigInteger('amount_cents');
            $table->text('message')->nullable();
            $table->string('status')->default('logged'); // logged (self-reported); reserved for future gateways
            $table->timestamps();

            $table->index('registry_fund_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registry_contributions');
        Schema::dropIfExists('registry_items');
        Schema::dropIfExists('registry_funds');
    }
};
