<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_profiles', function (Blueprint $table) {
            // Connect onboarding state, synced from Stripe's account.updated webhook
            // (and on the onboarding return). A vendor can only receive payouts
            // once charges are enabled.
            $table->boolean('stripe_charges_enabled')->default(false)->after('stripe_account_id');
            $table->boolean('stripe_details_submitted')->default(false)->after('stripe_charges_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_profiles', function (Blueprint $table) {
            $table->dropColumn(['stripe_charges_enabled', 'stripe_details_submitted']);
        });
    }
};
