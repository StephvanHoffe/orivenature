<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Afbeeldingen uit public/uploads in een vaste set breedtes als WebP.
 * De eerste aanvraag maakt het bestand aan (route /media/...), daarna serveert
 * de webserver het direct uit public/media.
 */
class Media
{
    public const WIDTHS = [120, 200, 400, 600, 900, 1200, 1600];

    public static function url(?string $path, int $width = 900): string
    {
        if (! $path) {
            return '';
        }
        if (Str::startsWith($path, ['http://', 'https://', '/'])) {
            return $path;
        }
        $width = self::nearestWidth($width);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) || ! function_exists('imagewebp')) {
            return asset('uploads/'.$path);
        }

        return asset('media/'.$width.'/'.$path.'.webp');
    }

    public static function srcset(?string $path, array $widths = [400, 600, 900, 1200]): string
    {
        return collect($widths)->map(fn ($w) => self::url($path, $w).' '.$w.'w')->implode(', ');
    }

    public static function nearestWidth(int $width): int
    {
        foreach (self::WIDTHS as $w) {
            if ($w >= $width) {
                return $w;
            }
        }

        return end(self::WIDTHS);
    }

    /** Maakt de verkleinde WebP aan en geeft het pad terug, of null als het bronbestand niet bestaat. */
    public static function generate(int $width, string $path): ?string
    {
        if (! in_array($width, self::WIDTHS, true) || str_contains($path, '..')) {
            return null;
        }
        $source = public_path('uploads/'.$path);
        if (! is_file($source)) {
            return null;
        }
        $target = public_path('media/'.$width.'/'.$path.'.webp');
        if (is_file($target)) {
            return $target;
        }
        $info = @getimagesize($source);
        if (! $info) {
            return null;
        }
        $image = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($source),
            IMAGETYPE_PNG => @imagecreatefrompng($source),
            IMAGETYPE_WEBP => @imagecreatefromwebp($source),
            IMAGETYPE_GIF => @imagecreatefromgif($source),
            default => false,
        };
        if (! $image) {
            return null;
        }
        [$w, $h] = [$info[0], $info[1]];
        $newW = min($width, $w);
        $newH = (int) round($h * ($newW / $w));
        $resized = imagecreatetruecolor($newW, $newH);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newW, $newH, $w, $h);
        @mkdir(dirname($target), 0755, true);
        imagewebp($resized, $target, 82);
        imagedestroy($image);
        imagedestroy($resized);

        return $target;
    }
}
