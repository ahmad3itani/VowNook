<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds an explicit display order to gallery photos so couples can drag-and-drop
 * to arrange them. Existing rows default to 0 and fall back to id ordering.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gallery_photos', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('caption');
            $table->index(['wedding_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::table('gallery_photos', function (Blueprint $table) {
            $table->dropIndex(['wedding_id', 'sort_order']);
            $table->dropColumn('sort_order');
        });
    }
};
