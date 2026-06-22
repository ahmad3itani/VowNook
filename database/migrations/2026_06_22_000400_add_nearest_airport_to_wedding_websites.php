<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wedding_websites', function (Blueprint $table) {
            // The wedding's nearest major airport (IATA code, e.g. "YYZ"), used
            // to pre-fill the affiliate flight search for fly-in guests.
            $table->string('nearest_airport', 60)->nullable()->after('show_travel_stays');
        });
    }

    public function down(): void
    {
        Schema::table('wedding_websites', function (Blueprint $table) {
            $table->dropColumn('nearest_airport');
        });
    }
};
