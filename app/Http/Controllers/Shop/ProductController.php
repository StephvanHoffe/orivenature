<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Analytics;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    private function find(string $handle): Product
    {
        return Product::active()->where('handle', $handle)->with(['variants.image', 'images', 'collections'])->firstOrFail();
    }

    public function show(Request $request, string $handle)
    {
        $product = $this->find($handle);
        $variant = $product->variants->firstWhere('id', (int) $request->query('variant')) ?? $product->defaultVariant();
        Analytics::event('view_product', $product->id);

        $related = Product::active()->with(['variants.image', 'images'])
            ->whereHas('collections', fn ($q) => $q->whereIn('collections.id', $product->collections->pluck('id')))
            ->whereKeyNot($product->id)->orderBy('position')->take(3)->get();
        if ($related->count() < 3) {
            $related = $related->merge(Product::active()->with(['variants.image', 'images'])->whereKeyNot($product->id)
                ->whereNotIn('id', $related->pluck('id'))->orderBy('position')->take(3 - $related->count())->get());
        }

        return view('shop.product', compact('product', 'variant', 'related'));
    }

    public function quickView(Request $request, string $handle)
    {
        $product = $this->find($handle);
        $variant = $product->variants->firstWhere('id', (int) $request->query('variant')) ?? $product->defaultVariant();

        return view('shop.partials.product-info', ['product' => $product, 'variant' => $variant, 'quick' => true]);
    }
}
