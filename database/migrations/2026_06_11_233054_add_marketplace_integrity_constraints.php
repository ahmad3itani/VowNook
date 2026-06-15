<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hardening pass: enforce one booking per inquiry and one profile per vendor
 * user at the DB level (the controllers already check, but constraints close
 * the race window), and index the hot foreign keys that SQLite/Postgres do
 * not index automatically.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->unique('inquiry_id');
            $table->index(['wedding_id', 'status']);
        });

        Schema::table('vendor_profiles', function (Blueprint $table) {
            $table->unique('user_id');
        });

        Schema::table('inquiry_messages', function (Blueprint $table) {
            $table->index('inquiry_id');
        });

        Schema::table('offers', function (Blueprint $table) {
            $table->index('inquiry_id');
        });

        Schema::table('inquiries', function (Blueprint $table) {
            $table->index('wedding_id');
            $table->index('vendor_profile_id');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropUnique(['inquiry_id']);
            $table->dropIndex(['wedding_id', 'status']);
        });

        Schema::table('vendor_profiles', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
        });

        Schema::table('inquiry_messages', function (Blueprint $table) {
            $table->dropIndex(['inquiry_id']);
        });

        Schema::table('offers', function (Blueprint $table) {
            $table->dropIndex(['inquiry_id']);
        });

        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropIndex(['wedding_id']);
            $table->dropIndex(['vendor_profile_id']);
        });
    }
};
