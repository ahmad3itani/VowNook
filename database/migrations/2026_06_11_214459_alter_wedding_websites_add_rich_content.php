<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wedding_websites', function (Blueprint $table) {
            $table->string('template')->default('classic')->after('is_published');
            $table->string('hero_image_path')->nullable()->after('hero_image_url');
            $table->string('hero_video_url')->nullable()->after('hero_image_path');
            $table->string('story_image_path')->nullable()->after('our_story');
            $table->json('timeline_items')->nullable()->after('story_image_path');
            $table->string('video_url')->nullable()->after('timeline_items');
        });
    }

    public function down(): void
    {
        Schema::table('wedding_websites', function (Blueprint $table) {
            $table->dropColumn([
                'template',
                'hero_image_path',
                'hero_video_url',
                'story_image_path',
                'timeline_items',
                'video_url',
            ]);
        });
    }
};
