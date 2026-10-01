<?php

namespace App\Models;

use App\Support\Media;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Article extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['tags' => 'array', 'is_published' => 'boolean', 'published_at' => 'datetime'];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function url(): string
    {
        return '/blogs/'.$this->blog.'/'.$this->handle;
    }

    public function imageUrl(int $width = 800): ?string
    {
        return $this->image ? Media::url($this->image, $width) : null;
    }

    public function excerptText(int $words = 30): string
    {
        $text = $this->excerpt ?: strip_tags((string) $this->body);

        return Str::words(trim(preg_replace('/\s+/', ' ', html_entity_decode($text))), $words);
    }
}
