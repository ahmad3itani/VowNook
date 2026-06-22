<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('honeymoon_plans', function (Blueprint $table) {
            // The couple's intake (vibe, budget, departure, interests…).
            $table->json('preferences')->nullable()->after('notes');
            // The 3 AI-generated, budget-tiered packages they compare.
            $table->json('packages')->nullable()->after('preferences');
            // Which tier they picked: essential | signature | dream.
            $table->string('chosen_tier', 20)->nullable()->after('packages');
        });
    }

    public function down(): void
    {
        Schema::table('honeymoon_plans', function (Blueprint $table) {
            $table->dropColumn(['preferences', 'packages', 'chosen_tier']);
        });
    }
};
