<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Loyalty;

class Storefront
{
    /**
     * Opties voor een productkaart met inhoudskeuze (bijv. 50 / 100 gram).
     */
    public static function cardOptions(Product $product): array
    {
        $variants = $product->variants;
        $first = $variants->first();

        return $variants->values()->map(fn (ProductVariant $v, int $i) => [
            'label' => $product->hasOnlyDefaultVariant() ? $product->title : $v->title,
            'variantId' => $v->id,
            'available' => $v->isAvailable(),
            'price' => money($v->price),
            'compareAt' => $v->compare_at_price > $v->price ? money($v->compare_at_price) : null,
            'url' => $product->url($v),
            'image' => $v->imageUrl(600),
            'alt' => $v->displayTitle(),
            'bestseller' => $v->is_bestseller,
            'save' => $i > 0 ? self::saveText($first, $v) : null,
            'points' => Loyalty::earnText($v->price),
        ])->all();
    }

    /** "bespaar €12,95 t.o.v. 2× 50 gram" als de grote verpakking voordeliger is dan meerdere kleine. */
    public static function saveText(?ProductVariant $small, ProductVariant $big): ?string
    {
        $template = (string) settings('products.save_text');
        if (! $small || $template === '') {
            return null;
        }
        $a = (float) preg_replace('/[^0-9.,]/', '', str_replace(',', '.', (string) $small->title));
        $b = (float) preg_replace('/[^0-9.,]/', '', str_replace(',', '.', (string) $big->title));
        if ($a <= 0 || $b <= $a) {
            return null;
        }
        $ratio = $b / $a;
        if (abs($ratio - round($ratio)) > 0.01) {
            return null;
        }
        $saving = (int) round($small->price * $ratio) - $big->price;
        if ($saving <= 0) {
            return null;
        }

        return str_replace(['[amount]', '[size]'], [money($saving), (string) $small->title], str_replace('2×', ((int) round($ratio)).'×', $template));
    }

    public static function whatsappUrl(): ?string
    {
        $number = preg_replace('/\D/', '', (string) settings('store.whatsapp'));

        return $number ? 'https://wa.me/'.$number : null;
    }
}
