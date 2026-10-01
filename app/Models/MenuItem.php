<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        // Subitems horen bij hetzelfde menu als hun bovenliggende item
        static::creating(function (MenuItem $item) {
            if (! $item->menu_id && $item->parent_id) {
                $item->menu_id = static::whereKey($item->parent_id)->value('menu_id');
            }
        });
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('position');
    }

    public function isCurrent(): bool
    {
        $path = '/'.ltrim(parse_url($this->url, PHP_URL_PATH) ?? '', '/');

        return $path !== '/' && request()->is(ltrim($path, '/'));
    }
}
