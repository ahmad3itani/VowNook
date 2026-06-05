<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Budget line items. Money is stored in integer cents to avoid floating
 * point rounding errors across sums.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('budget_categories')->nullOnDelete();
            $table->string('name');
            $table->unsignedBigInteger('estimated_cents')->default(0);
            $table->unsignedBigInteger('actual_cents')->nullable();
            $table->unsignedBigInteger('paid_cents')->default(0);
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['wedding_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_items');
    }
};
