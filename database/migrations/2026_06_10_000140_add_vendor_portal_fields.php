<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->date('follow_up_at')->nullable()->after('notes');
            $table->string('contract_status', 20)->nullable()->after('follow_up_at'); // pending|received|signed
            $table->string('coi_status', 20)->nullable()->after('contract_status');   // pending|received|on_file
            $table->foreignId('vendor_user_id')->nullable()->constrained('users')->nullOnDelete()->after('coi_status');
        });

        // Allow vendor-linked users to be tracked in wedding_user pivot.
        // We reuse the existing pivot; no new table needed.
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropForeign(['vendor_user_id']);
            $table->dropColumn(['follow_up_at', 'contract_status', 'coi_status', 'vendor_user_id']);
        });
    }
};
