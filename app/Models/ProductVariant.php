<?php

namespace App\Models;

use App\Support\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'track_stock' => 'boolean',
        'allow_backorder' => 'boolean',
        'is_bestseller' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(ProductImage::class, 'image_id');
    }

    public function isAvailable(): bool
    {
        return ! $this->track_stock || $this->allow_backorder || $this->stock > 0;
    }

    public function availableQuantity(): ?int
    {
        return $this->track_stock && ! $this->allow_backorder ? max(0, $this->stock) : null;
    }

    public function displayTitle(): string
    {
        return $this->product->hasOnlyDefaultVariant() ? $this->product->title : $this->product->title.' – '.$this->title;
    }

    public function imageUrl(int $width = 600): ?string
    {
        if ($this->image) {
            return Media::url($this->image->path, $width);
        }

        return $this->product->imageUrl($width);
    }
}
