<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // When the referred-side $20-off-Atelier discount was redeemed (via a
            // completed Stripe Checkout). Nullable — unset means eligible (if
            // referred_by is also set); set means already used, one-time only.
            $table->timestamp('referral_discount_used_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('referral_discount_used_at');
        });
    }
};
