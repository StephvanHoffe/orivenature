<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Support\Media;

/** Maakt verkleinde WebP-afbeeldingen aan op de eerste aanvraag. */
class MediaController extends Controller
{
    public function __invoke(int $width, string $path)
    {
        $source = preg_replace('/\.webp$/', '', $path);
        $file = Media::generate($width, $source);
        abort_unless($file, 404);

        return response()->file($file, ['Content-Type' => 'image/webp', 'Cache-Control' => 'public, max-age=31536000, immutable']);
    }
}
