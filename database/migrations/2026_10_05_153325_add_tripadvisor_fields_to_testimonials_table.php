<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->string('external_id', 64)->nullable()->after('source');
            $table->string('title')->nullable()->after('location');
            $table->string('url')->nullable()->after('external_id');
            $table->date('reviewed_at')->nullable()->after('url');

            $table->unique(['source', 'external_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->dropUnique(['source', 'external_id']);
            $table->dropColumn(['external_id', 'title', 'url', 'reviewed_at']);
        });
    }
};
