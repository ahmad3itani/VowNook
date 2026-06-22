<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('honeymoon_plans', function (Blueprint $table) {
            // Whether the chosen package has been turned into registry funds
            // guests can contribute toward.
            $table->boolean('registry_added')->default(false)->after('chosen_tier');
        });
    }

    public function down(): void
    {
        Schema::table('honeymoon_plans', function (Blueprint $table) {
            $table->dropColumn('registry_added');
        });
    }
};
