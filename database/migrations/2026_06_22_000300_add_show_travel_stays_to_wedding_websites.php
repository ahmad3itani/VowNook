<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wedding_websites', function (Blueprint $table) {
            // Whether to show the affiliate "stays near your venue" map on the
            // public site. On by default; couples can hide it from the editor.
            $table->boolean('show_travel_stays')->default(true)->after('travel_notes');
        });
    }

    public function down(): void
    {
        Schema::table('wedding_websites', function (Blueprint $table) {
            $table->dropColumn('show_travel_stays');
        });
    }
};
