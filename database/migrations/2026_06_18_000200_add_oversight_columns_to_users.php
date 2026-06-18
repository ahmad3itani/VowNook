<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Support/oversight columns on users: last-login tracking for the admin console
 * and a soft account suspension (suspended users are signed out and blocked from
 * the app until an admin lifts it).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->timestamp('suspended_at')->nullable()->after('last_login_ip');
            $table->string('suspended_reason')->nullable()->after('suspended_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_login_at', 'last_login_ip', 'suspended_at', 'suspended_reason']);
        });
    }
};
