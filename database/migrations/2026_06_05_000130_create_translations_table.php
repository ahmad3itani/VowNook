<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Editable UI strings per locale. Admins override copy here; the app falls back
 * to the in-code default when a key has no translation for the active locale.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 10);
            $table->string('key');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['locale', 'key']);
            $table->index('locale');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
