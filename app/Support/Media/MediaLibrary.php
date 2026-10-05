<?php

namespace App\Support\Media;

use App\Models\Media;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Looks up the manifest of public images and builds responsive <picture>
 * markup (AVIF → WebP → original), always with intrinsic width/height so
 * images never cause layout shift.
 */
class MediaLibrary
{
    public const CACHE_KEY = 'media.manifest';

    /**
     * @var array<string, array{w: ?int, h: ?int, v: list<array{format: string, width: int, path: string}>}>|null
     */
    private ?array $manifest = null;

    /**
     * @return array{w: ?int, h: ?int, v: list<array{format: string, width: int, path: string}>}|null
     */
    public function find(?string $path): ?array
    {
        if (blank($path)) {
            return null;
        }

        return $this->manifest()[strtolower(rawurldecode($path))] ?? null;
    }

    /**
     * @param  array{alt?: ?string, class?: ?string, sizes?: ?string, eager?: bool, priority?: bool, width?: ?int, height?: ?int, title?: ?string}  $options
     */
    public function picture(string $src, array $options = []): string
    {
        $media = $this->find($src);

        if ($media === null && ! $this->exists($src)) {
            return '';
        }

        $alt = e($options['alt'] ?? '');
        $class = filled($options['class'] ?? null) ? ' class="'.e($options['class']).'"' : '';
        $title = filled($options['title'] ?? null) ? ' title="'.e($options['title']).'"' : '';
        $sizes = $options['sizes'] ?? '(min-width: 1024px) 800px, 100vw';
        $eager = (bool) ($options['eager'] ?? false);
        $priority = (bool) ($options['priority'] ?? false);

        $width = $media['w'] ?? ($options['width'] ?? null);
        $height = $media['h'] ?? ($options['height'] ?? null);
        $dimensions = $width && $height ? ' width="'.$width.'" height="'.$height.'"' : '';
        $loading = $eager ? ' loading="eager"'.($priority ? ' fetchpriority="high"' : '') : ' loading="lazy"';

        $sources = '';
        foreach (['avif', 'webp'] as $format) {
            $srcset = $this->srcset($media, $format);
            if ($srcset !== '') {
                $sources .= '<source type="image/'.$format.'" srcset="'.e($srcset).'" sizes="'.e($sizes).'">';
            }
        }

        $img = '<img src="'.e($this->encode($src)).'" alt="'.$alt.'"'.$title.$dimensions.$class.$loading.' decoding="async">';

        return $sources === '' ? $img : '<picture>'.$sources.$img.'</picture>';
    }

    /**
     * Smallest variant suitable as a preload / social image candidate.
     */
    public function bestVariant(?string $path, int $minWidth = 1200, string $format = 'webp'): ?string
    {
        $media = $this->find($path);

        $variants = collect($media['v'] ?? [])->where('format', $format)->sortBy('width');

        return $variants->first(fn (array $variant): bool => $variant['width'] >= $minWidth)['path']
            ?? $variants->last()['path']
            ?? null;
    }

    /**
     * @param  array{w: ?int, h: ?int, v: list<array{format: string, width: int, path: string}>}|null  $media
     */
    public function srcset(?array $media, string $format): string
    {
        return collect($media['v'] ?? [])
            ->where('format', $format)
            ->sortBy('width')
            ->map(fn (array $variant): string => $this->encode($variant['path']).' '.$variant['width'].'w')
            ->implode(', ');
    }

    /**
     * Remote images are assumed to exist; local ones must be on disk.
     */
    public function exists(?string $src): bool
    {
        if (blank($src)) {
            return false;
        }

        if (preg_match('#^(https?:)?//#i', $src)) {
            return true;
        }

        return $this->find($src) !== null || is_file(public_path(ltrim(rawurldecode($src), '/')));
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function encode(string $path): string
    {
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        return implode('/', array_map(fn (string $segment): string => rawurlencode(rawurldecode($segment)), explode('/', $path)));
    }

    /**
     * @return array<string, array{w: ?int, h: ?int, v: list<array{format: string, width: int, path: string}>}>
     */
    private function manifest(): array
    {
        if ($this->manifest !== null) {
            return $this->manifest;
        }

        try {
            return $this->manifest = Cache::rememberForever(self::CACHE_KEY, fn (): array => Media::query()
                ->get(['path', 'width', 'height', 'variants'])
                ->mapWithKeys(fn (Media $media): array => [strtolower($media->path) => ['w' => $media->width, 'h' => $media->height, 'v' => $media->variants ?? []]])
                ->all());
        } catch (Throwable) {
            return $this->manifest = [];
        }
    }
}
