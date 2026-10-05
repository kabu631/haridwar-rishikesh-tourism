<?php

namespace App\Support\Media;

use App\Models\Media;
use GdImage;
use Illuminate\Support\Facades\File;
use Throwable;

/**
 * Generates responsive WebP (and optionally AVIF) variants for a public image
 * and records its intrinsic size. The original file is never modified or
 * moved, so its URL (indexed by Google Images) keeps working.
 */
class ImageProcessor
{
    /**
     * Responsive widths generated when the original is wider.
     */
    public const WIDTHS = [480, 800, 1200, 1600];

    private const MAX_WIDTH = 1920;

    public function process(string $publicPath, bool $withAvif = false, bool $force = false): ?Media
    {
        $publicPath = '/'.ltrim($publicPath, '/');
        $file = public_path(ltrim($publicPath, '/'));

        $existing = Media::query()->where('path', $publicPath)->first();
        if ($existing !== null && ! $force && (! $withAvif || $this->hasFormat($existing, 'avif'))) {
            return $existing;
        }

        if (! File::exists($file)) {
            return null;
        }

        $info = @getimagesize($file);
        if ($info === false) {
            return null;
        }

        [$width, $height] = $info;
        $mime = $info['mime'];

        $variants = [];

        if (in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true) && $width > 0) {
            $source = $this->open($file, $mime);

            if ($source instanceof GdImage) {
                $widths = array_values(array_filter(self::WIDTHS, fn (int $candidate): bool => $candidate < $width));
                $widths[] = min($width, self::MAX_WIDTH);

                foreach (array_unique($widths) as $targetWidth) {
                    $variants = array_merge($variants, $this->writeVariants($source, $publicPath, $width, $height, $targetWidth, $withAvif));
                }
            }
        }

        return Media::query()->updateOrCreate(['path' => $publicPath], [
            'width' => $width,
            'height' => $height,
            'bytes' => File::size($file),
            'variants' => $variants,
        ]);
    }

    /**
     * @return list<array{format: string, width: int, path: string}>
     */
    private function writeVariants(GdImage $source, string $publicPath, int $width, int $height, int $targetWidth, bool $withAvif): array
    {
        $targetHeight = (int) round($height * ($targetWidth / $width));
        $resized = $targetWidth === $width ? $source : $this->resize($source, $width, $height, $targetWidth, $targetHeight);

        $written = [];
        $formats = $withAvif && function_exists('imageavif') ? ['webp', 'avif'] : ['webp'];

        foreach ($formats as $format) {
            $variantPath = $this->variantPath($publicPath, $targetWidth, $format);
            $target = public_path(ltrim($variantPath, '/'));
            File::ensureDirectoryExists(dirname($target));

            try {
                $ok = $format === 'avif'
                    ? imageavif($resized, $target, 52, 8)
                    : imagewebp($resized, $target, 80);
            } catch (Throwable) {
                $ok = false;
            }

            if ($ok && File::exists($target)) {
                $written[] = ['format' => $format, 'width' => $targetWidth, 'path' => $variantPath];
            }
        }

        return $written;
    }

    /**
     * "/header/haridwar.jpg" → "/_optimized/header/haridwar-jpg-800.webp"
     */
    public function variantPath(string $publicPath, int $width, string $format): string
    {
        $info = pathinfo(ltrim($publicPath, '/'));
        $directory = ($info['dirname'] ?? '.') === '.' ? '' : $info['dirname'].'/';
        $name = strtolower(preg_replace('/[^A-Za-z0-9_\-]+/', '-', $info['filename']));
        $extension = strtolower($info['extension'] ?? 'img');

        return '/_optimized/'.$directory.$name.'-'.$extension.'-'.$width.'.'.$format;
    }

    private function open(string $file, string $mime): ?GdImage
    {
        try {
            $image = match ($mime) {
                'image/jpeg' => @imagecreatefromjpeg($file),
                'image/png' => @imagecreatefrompng($file),
                'image/webp' => @imagecreatefromwebp($file),
                default => false,
            };
        } catch (Throwable) {
            return null;
        }

        if (! $image instanceof GdImage) {
            return null;
        }

        if (! imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }

        imagealphablending($image, true);
        imagesavealpha($image, true);

        return $image;
    }

    private function resize(GdImage $source, int $width, int $height, int $targetWidth, int $targetHeight): GdImage
    {
        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefilledrectangle($canvas, 0, 0, $targetWidth, $targetHeight, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        return $canvas;
    }

    private function hasFormat(Media $media, string $format): bool
    {
        return collect($media->variants ?? [])->contains('format', $format);
    }
}
