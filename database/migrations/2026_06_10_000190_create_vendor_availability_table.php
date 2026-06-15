<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_availability', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_profile_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('status', 20)->default('blocked'); // booked|blocked
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['vendor_profile_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_availability');
    }
};
