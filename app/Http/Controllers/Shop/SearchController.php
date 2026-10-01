<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $products = $articles = $pages = collect();
        if ($q !== '') {
            $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';
            $products = Product::active()->with(['variants.image', 'images'])
                ->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('description', 'like', $like)->orWhere('product_type', 'like', $like))
                ->orderBy('position')->take(24)->get();
            $articles = Article::published()->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('body', 'like', $like))->take(8)->get();
            $pages = Page::where('is_published', true)->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('body', 'like', $like))->take(5)->get();
        }

        return view('shop.search', compact('q', 'products', 'articles', 'pages'));
    }
}
