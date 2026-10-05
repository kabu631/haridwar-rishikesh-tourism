@props(['items' => []])
@if (count($items) > 1)
    <nav aria-label="Breadcrumb" {{ $attributes }}>
        <ol class="flex flex-wrap items-center gap-x-1.5 gap-y-1 text-sm">
            @foreach ($items as $crumb)
                <li class="flex items-center gap-1.5">
                    @if (! $loop->last)
                        <a href="{{ parse_url($crumb['url'], PHP_URL_PATH) ?: '/' }}" class="opacity-80 transition hover:opacity-100 hover:underline">{{ $crumb['name'] }}</a>
                        <x-glyph name="chevron-right" class="size-3.5 opacity-60" />
                    @else
                        <span aria-current="page" class="font-medium">{{ \Illuminate\Support\Str::limit($crumb['name'], 60) }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
