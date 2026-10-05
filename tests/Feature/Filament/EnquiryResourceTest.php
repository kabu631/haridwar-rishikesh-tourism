<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Enquiries\Pages\ListEnquiries;
use App\Models\Enquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EnquiryResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_enquiry_list_hides_spam_by_default(): void
    {
        $lead = Enquiry::factory()->create();
        $spam = Enquiry::factory()->spam()->create();
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(ListEnquiries::class)
            ->assertCanSeeTableRecords([$lead])
            ->assertCanNotSeeTableRecords([$spam]);
    }

    public function test_marking_enquiries_as_contacted_updates_their_status(): void
    {
        $lead = Enquiry::factory()->create();
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(ListEnquiries::class)->callTableBulkAction('markContacted', [$lead]);

        $this->assertSame('contacted', $lead->fresh()->status);
    }
}
