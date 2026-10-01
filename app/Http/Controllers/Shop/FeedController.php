<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Collection;
use App\Models\Page;
use App\Models\Product;

class FeedController extends Controller
{
    /** Productcatalogus voor Meta (Facebook/Instagram-shop en dynamische advertenties). */
    public function metaCatalog()
    {
        abort_unless(settings('meta.catalog_enabled'), 404);
        $products = Product::active()->with(['variants.image', 'images'])->orderBy('position')->get();

        return response()->view('shop.feeds.meta-catalog', compact('products'))->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function sitemap()
    {
        $urls = collect([['loc' => url('/'), 'lastmod' => now()]])
            ->merge(Product::active()->get()->map(fn ($p) => ['loc' => url($p->url()), 'lastmod' => $p->updated_at]))
            ->merge(Collection::where('is_visible', true)->get()->map(fn ($c) => ['loc' => url($c->url()), 'lastmod' => $c->updated_at]))
            ->merge(Page::where('is_published', true)->get()->map(fn ($p) => ['loc' => url($p->url()), 'lastmod' => $p->updated_at]))
            ->merge(Article::published()->get()->map(fn ($a) => ['loc' => url($a->url()), 'lastmod' => $a->updated_at]));

        return response()->view('shop.feeds.sitemap', compact('urls'))->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots()
    {
        $lines = [
            'User-agent: *',
            'Disallow: /beheer',
            'Disallow: /cart',
            'Disallow: /checkout',
            'Disallow: /account',
            'Disallow: /orders',
            'Disallow: /search',
            'Sitemap: '.url('/sitemap.xml'),
        ];

        return response(implode("\n", $lines)."\n")->header('Content-Type', 'text/plain');
    }
}
