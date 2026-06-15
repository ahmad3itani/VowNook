<?php

use App\Enums\BlogCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('excerpt', 500)->nullable();
            $table->longText('body'); // markdown
            $table->string('cover_image_path')->nullable();
            $table->string('category')->default(BlogCategory::PlanningTips->value)->index();
            $table->string('author_name')->default('VowNook');
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 300)->nullable();
            $table->string('status', 20)->default('draft')->index();
            // Null until live; a future timestamp = scheduled.
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};
