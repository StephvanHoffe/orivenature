<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Product;

class HomeController extends Controller
{
    public function __invoke()
    {
        $sections = collect((array) settings('homepage.sections'))->filter(fn ($s) => ! empty($s['type']) && empty($s['data']['hidden']));

        // Alle producten die de secties nodig hebben in één keer laden
        $handles = $sections->flatMap(fn ($s) => array_merge(
            (array) ($s['data']['products'] ?? []),
            (array) ($s['data']['flavours'] ?? []),
            [$s['data']['product'] ?? null],
        ))->filter()->unique()->values();
        $products = Product::active()->with(['variants.image', 'images', 'collections:id'])->whereIn('handle', $handles)->get()->keyBy('handle');

        $blogSection = $sections->firstWhere('type', 'blog');
        $articles = $blogSection
            ? Article::published()->where('blog', $blogSection['data']['blog'] ?? 'news')->latest('published_at')->take((int) ($blogSection['data']['count'] ?? 4))->get()
            : collect();

        return view('shop.home', compact('sections', 'products', 'articles'));
    }
}
