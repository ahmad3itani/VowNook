<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Seats a guest at a reception table. Nulling on delete keeps the guest but
 * frees their seat when a table is removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->foreignId('table_id')->nullable()->after('group_id')
                ->constrained('seating_tables')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('table_id');
        });
    }
};
