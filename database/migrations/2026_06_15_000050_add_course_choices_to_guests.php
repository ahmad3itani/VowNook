<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            // meal_choice stays the MAIN course (kept for seating/dashboard/exports).
            // These two cover the optional appetizer and dessert courses.
            $table->string('appetizer_choice')->nullable()->after('meal_choice');
            $table->string('dessert_choice')->nullable()->after('appetizer_choice');
        });
    }

    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->dropColumn(['appetizer_choice', 'dessert_choice']);
        });
    }
};
