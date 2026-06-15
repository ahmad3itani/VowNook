<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Downscales and re-encodes uploaded images before storage so galleries and
 * hero images stay fast in production. Prefers WebP (≈30% smaller than JPEG at
 * equal quality, supported by every current browser) for a Core-Web-Vitals win;
 * falls back to JPEG/PNG, then to storing the original untouched when the image
 * can't be decoded or GD is unavailable. Re-encoding also strips EXIF metadata
 * (GPS location etc.) from guest-facing photos.
 */
class ImageOptimizer
{
    private const QUALITY = 82;

    /**
     * Optimize and store an uploaded image on the default disk.
     *
     * @param  int  $maxEdge  longest edge in pixels; larger images are scaled down
     * @return string the stored path
     */
    public static function store(UploadedFile $file, string $dir, int $maxEdge = 2000): string
    {
        return self::storeWithMeta($file, $dir, $maxEdge)['path'];
    }

    /**
     * Like store(), but also returns the final pixel dimensions (for emitting
     * width/height on <img> and in schema.org ImageObject).
     *
     * @return array{path:string,width:?int,height:?int}
     */
    public static function storeWithMeta(UploadedFile $file, string $dir, int $maxEdge = 2000): array
    {
        $mime = $file->getMimeType();

        // Pass through formats GD shouldn't re-encode (animated gif, svg, …).
        if (! function_exists('imagecreatefromstring')
            || ! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return ['path' => $file->store($dir), 'width' => null, 'height' => null];
        }

        // Decoding a large photo needs ~5 bytes/pixel plus a scaled copy —
        // more than a default 128M limit allows for phone-camera images.
        @ini_set('memory_limit', '512M');

        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if ($source === false) {
            return ['path' => $file->store($dir), 'width' => null, 'height' => null];
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $longest = max($width, $height);

        if ($longest > $maxEdge) {
            $scaled = imagescale($source, (int) round($width * $maxEdge / $longest), -1, IMG_BICUBIC);

            if ($scaled !== false) {
                imagedestroy($source);
                $source = $scaled;
                $width = imagesx($source);
                $height = imagesy($source);
            }
        }

        // Preserve transparency (logos) through the re-encode.
        imagealphablending($source, false);
        imagesavealpha($source, true);

        $webp = function_exists('imagewebp');
        $ext = $webp ? '.webp' : ($mime === 'image/png' ? '.png' : '.jpg');

        ob_start();
        if ($webp) {
            imagewebp($source, null, self::QUALITY);
        } elseif ($mime === 'image/png') {
            imagepng($source, null, 6);
        } else {
            imagejpeg($source, null, self::QUALITY);
        }
        $binary = (string) ob_get_clean();
        imagedestroy($source);

        $path = $dir.'/'.Str::random(40).$ext;
        Storage::put($path, $binary);

        return ['path' => $path, 'width' => $width, 'height' => $height];
    }

    /**
     * Pixel dimensions of an already-stored image, or null if unreadable.
     *
     * @return array{0:int,1:int}|null
     */
    public static function dimensions(string $path): ?array
    {
        if (! Storage::exists($path)) {
            return null;
        }

        $info = @getimagesizefromstring((string) Storage::get($path));

        return $info ? [$info[0], $info[1]] : null;
    }
}
