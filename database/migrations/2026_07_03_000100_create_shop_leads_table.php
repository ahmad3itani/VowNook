<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Emails captured by the shop's "budget cheat sheet" opt-in. Each row records
// express consent (CASL) for the one thing the visitor asked for.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_leads', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('source')->default('shop');
            $table->timestamp('consented_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_leads');
    }
};
