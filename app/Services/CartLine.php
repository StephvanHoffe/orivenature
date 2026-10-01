<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;

class CartLine
{
    public int $discountAllocated = 0;

    public function __construct(
        public ProductVariant $variant,
        public int $quantity,
    ) {}

    public function product(): Product
    {
        return $this->variant->product;
    }

    public function unitPrice(): int
    {
        return $this->variant->price;
    }

    public function total(): int
    {
        return $this->unitPrice() * $this->quantity;
    }

    public function totalAfterDiscount(): int
    {
        return $this->total() - $this->discountAllocated;
    }

    public function taxRate(): float
    {
        return (float) $this->product()->tax_rate;
    }

    public function title(): string
    {
        return $this->product()->title;
    }

    public function variantTitle(): ?string
    {
        return $this->product()->hasOnlyDefaultVariant() ? null : $this->variant->title;
    }

    public function toArray(): array
    {
        return [
            'variant_id' => $this->variant->id,
            'product_id' => $this->variant->product_id,
            'title' => $this->title(),
            'variant_title' => $this->variantTitle(),
            'quantity' => $this->quantity,
            'price' => $this->unitPrice(),
            'image' => $this->variant->image?->path ?? $this->product()->featuredImage()?->path,
        ];
    }
}
