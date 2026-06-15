<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->morphs('reportable'); // VendorProfile, Review
            $table->string('reason');
            $table->text('details')->nullable();
            $table->foreignId('reporter_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('open'); // open | reviewed | actioned | dismissed
            $table->timestamps();
        });

        Schema::table('vendor_profiles', function (Blueprint $table) {
            $table->timestamp('verified_at')->nullable()->after('is_founding');
            $table->timestamp('agreement_accepted_at')->nullable()->after('verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_profiles', function (Blueprint $table) {
            $table->dropColumn(['verified_at', 'agreement_accepted_at']);
        });
        Schema::dropIfExists('reports');
    }
};
