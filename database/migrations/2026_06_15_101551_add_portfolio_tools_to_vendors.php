<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_media', function (Blueprint $table) {
            $table->string('alt_text')->nullable()->after('caption');
        });

        Schema::table('vendor_profiles', function (Blueprint $table) {
            $table->string('video_url')->nullable()->after('cover_path');
            $table->string('brochure_path')->nullable()->after('video_url');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_media', function (Blueprint $table) {
            $table->dropColumn('alt_text');
        });
        Schema::table('vendor_profiles', function (Blueprint $table) {
            $table->dropColumn(['video_url', 'brochure_path']);
        });
    }
};
