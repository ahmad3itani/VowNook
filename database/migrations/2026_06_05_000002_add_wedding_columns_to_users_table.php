<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('email');
            $table->string('plan')->default(config('plans.default', 'free'))->after('is_admin');
            $table->foreignId('current_wedding_id')->nullable()->after('plan')
                ->constrained('weddings')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_wedding_id');
            $table->dropColumn(['is_admin', 'plan']);
        });
    }
};
