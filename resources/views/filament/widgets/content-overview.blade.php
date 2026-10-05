<x-filament-widgets::widget>
    <x-filament::section heading="Website content" description="Published pages in each section of the sidebar.">
        <div style="display:grid;gap:.25rem">
            @foreach ($rows as $row)
                <a href="{{ $row['url'] }}" style="display:flex;align-items:center;gap:.75rem;padding:.6rem .5rem;border-radius:.5rem;text-decoration:none">
                    <x-filament::icon :icon="$row['icon']" style="width:1.25rem;height:1.25rem;opacity:.6" />
                    <span style="flex:1;font-size:.875rem;font-weight:500">{{ $row['label'] }}</span>
                    @if ($row['drafts'] > 0)
                        <x-filament::badge color="gray" size="sm">{{ $row['drafts'] }} draft{{ $row['drafts'] === 1 ? '' : 's' }}</x-filament::badge>
                    @endif
                    <span style="font-weight:700;font-variant-numeric:tabular-nums">{{ number_format($row['live']) }}</span>
                </a>
            @endforeach
        </div>
        <div style="margin-top:.75rem;padding-top:.75rem;border-top:1px solid rgb(0 0 0 / .08)">
            <x-filament::link :href="$allUrl" icon="heroicon-m-arrow-right" icon-position="after" size="sm">See all pages</x-filament::link>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
