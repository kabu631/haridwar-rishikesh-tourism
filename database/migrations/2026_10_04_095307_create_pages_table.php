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
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('path', 191)->unique()->comment('URL path without leading slash, e.g. har-ki-pauri.html; empty string is the homepage');
            $table->string('type', 30)->default('guide')->index();
            $table->string('section', 30)->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->string('title')->comment('Visible H1');
            $table->string('nav_label')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('body')->nullable();
            $table->mediumText('search_text')->nullable();
            $table->string('hero_image')->nullable();
            $table->string('hero_alt')->nullable();
            $table->json('cards')->nullable();
            $table->json('gallery')->nullable();
            $table->json('itinerary')->nullable();
            $table->json('faqs')->nullable();
            $table->json('facts')->nullable();
            $table->json('sources')->nullable();
            $table->json('extra')->nullable();
            $table->string('robots', 60)->default('index,follow');
            $table->string('canonical_url')->nullable();
            $table->string('schema_type', 40)->nullable();
            $table->foreignId('author_id')->nullable()->constrained('authors')->nullOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('authors')->nullOnDelete();
            $table->boolean('is_published')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['section', 'is_published', 'sort_order']);
            $table->fullText(['title', 'meta_title', 'meta_description', 'search_text'], 'pages_search_fulltext');
            $table->fullText(['title', 'meta_title'], 'pages_title_fulltext');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
