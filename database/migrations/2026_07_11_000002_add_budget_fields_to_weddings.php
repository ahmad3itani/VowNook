<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('weddings', function (Blueprint $table) {
            // The couple's total wedding budget (the number they "bring"), and the
            // Ontario city slug used to tune the allocation's realism and to match
            // nearby, budget-appropriate vendors. Both nullable — set when the
            // couple completes the budget-first setup, not required to exist.
            $table->unsignedBigInteger('total_budget_cents')->nullable();
            $table->string('city')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('weddings', function (Blueprint $table) {
            $table->dropColumn(['total_budget_cents', 'city']);
        });
    }
};
