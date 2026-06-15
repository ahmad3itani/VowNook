<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('kind')->default('comp_plan'); // comp_plan
            $table->string('plan')->default('premium');   // tier granted
            $table->unsignedInteger('duration_days')->default(30);
            $table->unsignedInteger('max_redemptions')->nullable(); // null = unlimited
            $table->unsignedInteger('redeemed_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('promo_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promo_code_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['promo_code_id', 'user_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            // A comped plan reverts to free when this passes (null = not comped).
            $table->timestamp('plan_comped_until')->nullable()->after('marketing_consent_at');
            $table->string('referral_code')->nullable()->unique()->after('plan_comped_until');
            $table->foreignId('referred_by')->nullable()->after('referral_code')
                ->constrained('users')->nullOnDelete();
            // Set once the referred user completes a qualifying action (so the
            // referrer is rewarded at most once per referral).
            $table->timestamp('referral_rewarded_at')->nullable()->after('referred_by');
        });

        Schema::table('vendor_profiles', function (Blueprint $table) {
            $table->boolean('is_founding')->default(false)->after('status');
            $table->timestamp('featured_until')->nullable()->after('is_founding');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_profiles', function (Blueprint $table) {
            $table->dropColumn(['is_founding', 'featured_until']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referred_by');
            $table->dropColumn(['plan_comped_until', 'referral_code']);
        });
        Schema::dropIfExists('promo_redemptions');
        Schema::dropIfExists('promo_codes');
    }
};
