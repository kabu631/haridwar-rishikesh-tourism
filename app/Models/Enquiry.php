<?php

namespace App\Models;

use Database\Factories\EnquiryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'type', 'name', 'email', 'phone', 'tour', 'page_id', 'travel_date', 'return_date', 'adults', 'children',
    'message', 'source_url', 'ip_address', 'user_agent', 'status', 'notes',
])]
class Enquiry extends Model
{
    /** @use HasFactory<EnquiryFactory> */
    use HasFactory;

    public const STATUSES = [
        'new' => 'New',
        'contacted' => 'Contacted',
        'quoted' => 'Quote sent',
        'booked' => 'Booked',
        'closed' => 'Closed',
        'spam' => 'Spam',
    ];

    public const TYPES = [
        'quick' => 'Quick enquiry',
        'booking' => 'Booking form',
        'package' => 'Package enquiry',
        'contact' => 'Contact form',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'travel_date' => 'date',
            'return_date' => 'date',
            'adults' => 'integer',
            'children' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Page, $this>
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
