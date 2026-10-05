<?php

namespace Tests\Feature;

use App\Enums\PageType;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogTest extends TestCase
{
    use RefreshDatabase;

    public function test_blog_page_lists_published_posts_newest_first(): void
    {
        Page::factory()->create(['path' => 'older-post.html', 'type' => PageType::Blog, 'title' => 'Older Kumbh Update', 'published_at' => now()->subMonth()]);
        Page::factory()->create(['path' => 'newer-post.html', 'type' => PageType::Blog, 'title' => 'Newer Char Dham Update', 'published_at' => now()->subDay()]);
        Page::factory()->unpublished()->create(['path' => 'draft-post.html', 'type' => PageType::Blog, 'title' => 'Draft Post Title']);

        $this->get('/blog.html')
            ->assertOk()
            ->assertSeeInOrder(['Newer Char Dham Update', 'Older Kumbh Update'])
            ->assertDontSee('Draft Post Title');
    }

    public function test_blog_posts_are_marked_up_as_blog_postings(): void
    {
        Page::factory()->create(['path' => 'ganga-dussehra-2026.html', 'type' => PageType::Blog]);

        $this->get('/ganga-dussehra-2026.html')->assertOk()->assertSee('"@type":"BlogPosting"', false);
    }

    public function test_admin_can_open_the_blog_section_and_add_a_post(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->get('/admin/pages?tab=blog')->assertOk()->assertSee('Add blog post');
        $this->get('/admin/pages/create?type=blog')->assertOk();
    }
}
