<?php

namespace App\Models;

use App\Support\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    protected $guarded = ['id'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function url(int $width = 900): string
    {
        return Media::url($this->path, $width);
    }

    /** Transparante PNG's zijn verpakkingen die vrij mogen zweven. */
    public function isPackshot(): bool
    {
        return str_ends_with(strtolower($this->path), '.png');
    }
}
