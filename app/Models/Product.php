<?php

namespace App\Models;

use App\Support\Media;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'tags' => 'array',
        'option_names' => 'array',
        'tax_rate' => 'decimal:2',
        'chip_highlight' => 'boolean',
        'published_at' => 'datetime',
    ];

    public const STATUSES = ['active' => 'Actief', 'draft' => 'Concept', 'archived' => 'Gearchiveerd'];

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('position')->orderBy('id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position')->orderBy('id');
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class)->withPivot('position');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function url(?ProductVariant $variant = null): string
    {
        $url = '/products/'.$this->handle;

        return $variant && $this->variants->count() > 1 ? $url.'?variant='.$variant->id : $url;
    }

    public function featuredImage(): ?ProductImage
    {
        return $this->images->first();
    }

    public function imageUrl(int $width = 600): ?string
    {
        $image = $this->featuredImage();

        return $image ? Media::url($image->path, $width) : null;
    }

    public function defaultVariant(): ?ProductVariant
    {
        return $this->variants->first(fn (ProductVariant $v) => $v->isAvailable()) ?? $this->variants->first();
    }

    public function minPrice(): int
    {
        return (int) $this->variants->min('price');
    }

    public function isAvailable(): bool
    {
        return $this->variants->contains(fn (ProductVariant $v) => $v->isAvailable());
    }

    public function hasOnlyDefaultVariant(): bool
    {
        return $this->variants->count() <= 1;
    }

    public function totalStock(): int
    {
        return (int) $this->variants->where('track_stock', true)->sum('stock');
    }
}
