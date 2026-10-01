<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Product;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    public function index(string $blog, ?string $tag = null)
    {
        $query = Article::published()->where('blog', $blog)->latest('published_at');
        abort_unless($blog === 'news' || (clone $query)->exists(), 404);
        $allTags = (clone $query)->pluck('tags')->flatten()->filter()->unique()->sort()->values();
        if ($tag) {
            $query->whereJsonContains('tags', $allTags->first(fn ($t) => Str::slug($t) === $tag) ?? $tag);
        }
        $articles = $query->paginate(12);

        return view('shop.blog', compact('blog', 'articles', 'allTags', 'tag'));
    }

    public function show(string $blog, string $handle)
    {
        $article = Article::published()->where('blog', $blog)->where('handle', $handle)->firstOrFail();
        $products = Product::active()->with(['variants.image', 'images'])->orderBy('position')->take(3)->get();

        return view('shop.article', compact('article', 'products'));
    }
}
