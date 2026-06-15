<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planner_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // 'checklist' | 'budget'
            $table->string('name');
            // checklist: [{title, category?, priority, offset_days|null, notes?}]
            //   offset_days is relative to the event date (negative = before).
            // budget: [{name, category?, estimated_cents, notes?}]
            $table->json('items');
            $table->timestamps();

            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planner_templates');
    }
};
