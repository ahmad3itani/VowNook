<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            // When the vendor first replied (offer or message). Drives the
            // "usually responds within X hours" badge.
            $table->timestamp('first_response_at')->nullable()->after('status');
        });

        Schema::table('vendor_profiles', function (Blueprint $table) {
            // Denormalized response stats (same pattern as rating_avg/rating_count).
            $table->unsignedSmallInteger('response_hours')->nullable()->after('rating_count');
            $table->unsignedInteger('response_count')->default(0)->after('response_hours');
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropColumn('first_response_at');
        });

        Schema::table('vendor_profiles', function (Blueprint $table) {
            $table->dropColumn(['response_hours', 'response_count']);
        });
    }
};
