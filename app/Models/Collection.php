<?php

namespace App\Models;

use App\Support\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Collection extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_visible' => 'boolean'];

    public const SORT_ORDERS = [
        'manual' => 'Handmatig',
        'title' => 'Alfabetisch',
        'price_asc' => 'Prijs laag-hoog',
        'price_desc' => 'Prijs hoog-laag',
        'newest' => 'Nieuwste eerst',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withPivot('position')->orderBy('collection_product.position');
    }

    public function url(): string
    {
        return '/collections/'.$this->handle;
    }

    public function imageUrl(int $width = 900): ?string
    {
        if ($this->image) {
            return Media::url($this->image, $width);
        }
        $product = $this->products()->with('images')->first();

        return $product?->imageUrl($width);
    }
}
