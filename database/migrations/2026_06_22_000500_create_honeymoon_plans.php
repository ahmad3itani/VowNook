<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('honeymoon_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('destination')->nullable();   // e.g. "Maui, Hawaii"
            $table->string('airport', 60)->nullable();    // nearest airport IATA, e.g. "OGG"
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->json('budget_items')->nullable();      // [{label, amount_cents}]
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('honeymoon_plans');
    }
};
