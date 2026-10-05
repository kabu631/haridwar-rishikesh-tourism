@props(['src', 'alt' => '', 'sizes' => '(min-width: 1024px) 33vw, 100vw', 'eager' => false, 'priority' => false])
@inject('media', 'App\Support\Media\MediaLibrary')
@if ($src && $media->exists($src))
    {!! $media->picture($src, ['alt' => $alt, 'class' => $attributes->get('class'), 'sizes' => $sizes, 'eager' => $eager, 'priority' => $priority]) !!}
@else
    <div {{ $attributes->merge(['class' => 'bg-sand-200']) }} role="presentation"></div>
@endif
