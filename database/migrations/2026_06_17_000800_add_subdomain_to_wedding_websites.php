<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A free personal web address: name.vownook.com resolves the couple's published
 * wedding site by Host header, alongside the canonical /w/{slug} URL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wedding_websites', function (Blueprint $table) {
            $table->string('subdomain')->nullable()->unique()->after('is_published');
        });
    }

    public function down(): void
    {
        Schema::table('wedding_websites', function (Blueprint $table) {
            $table->dropColumn('subdomain');
        });
    }
};
