<?php

namespace App\Models;

use App\Support\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['tax_rate' => 'decimal:2'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function imageUrl(int $width = 200): ?string
    {
        return $this->image ? Media::url($this->image, $width) : null;
    }

    public function unfulfilledQuantity(): int
    {
        return max(0, $this->quantity - $this->fulfilled_quantity - $this->refunded_quantity);
    }
}
