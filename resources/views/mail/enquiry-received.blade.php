<x-mail::message>
# New website enquiry

**{{ $enquiry->name }}** sent an enquiry from {{ config('site.name') }}.

<x-mail::table>
| | |
|:--|:--|
| Type | {{ \App\Models\Enquiry::TYPES[$enquiry->type] ?? $enquiry->type }} |
| Tour | {{ $enquiry->tour ?: '—' }} |
@if ($enquiry->page?->type === \App\Enums\PageType::Package)
| Tour page | [{{ $enquiry->page->absoluteUrl() }}]({{ $enquiry->page->absoluteUrl() }}) |
@foreach ($enquiry->page->tripFacts() as $label => $value)
| {{ $label }} | {{ $value }} |
@endforeach
@endif
| Email | {{ $enquiry->email }} |
| Phone | {{ $enquiry->phone }} |
| Travel date | {{ $enquiry->travel_date?->format('j M Y') ?? '—' }} |
| Return date | {{ $enquiry->return_date?->format('j M Y') ?? '—' }} |
| Travellers | {{ $enquiry->adults ?? '—' }} adults, {{ $enquiry->children ?? 0 }} children |
| Page | {{ $enquiry->source_url ?: '—' }} |
</x-mail::table>

@if ($enquiry->message)
**Message**

{{ $enquiry->message }}
@endif

<x-mail::button :url="url('/admin/enquiries/'.$enquiry->id)">
Open in admin panel
</x-mail::button>

Reply to this email to answer {{ $enquiry->name }} directly.
</x-mail::message>
