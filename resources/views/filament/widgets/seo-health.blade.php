<x-filament-widgets::widget>
    <x-filament::section heading="SEO health" description="Automatic on-page checks across {{ $total }} published pages. Legacy pages that already rank well are best changed carefully.">
        <div style="display:grid;gap:.75rem;grid-template-columns:repeat(auto-fit,minmax(240px,1fr))">
            @foreach ($checks as $check)
                @php($url = $check['filter'] ? $pagesUrl.'?filters['.$check['filter'].'][isActive]=true' : null)
                <a @if ($url) href="{{ $url }}" @endif style="display:flex;justify-content:space-between;align-items:center;gap:1rem;padding:.9rem 1rem;border-radius:.75rem;border:1px solid rgb(0 0 0 / .08);text-decoration:none">
                    <span style="font-size:.875rem">{{ $check['label'] }}</span>
                    <span style="font-weight:700;font-size:1.25rem;color:{{ $check['count'] === 0 ? '#02923b' : '#c95c10' }}">{{ $check['count'] }}</span>
                </a>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
