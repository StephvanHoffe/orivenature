<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    public function index()
    {
        $collections = Collection::where('is_visible', true)->withCount(['products' => fn ($q) => $q->where('status', 'active')])->orderBy('title')->get();

        return view('shop.collections', compact('collections'));
    }

    public function show(Request $request, string $handle)
    {
        $sort = $request->query('sort_by');
        if ($handle === 'all') {
            $collection = new Collection(['title' => __('Alle producten'), 'handle' => 'all', 'sort_order' => 'manual']);
            $query = Product::active()->orderBy('position')->orderBy('title');
        } else {
            $collection = Collection::where('handle', $handle)->where('is_visible', true)->firstOrFail();
            $query = $collection->products()->where('status', 'active');
        }
        $sort = array_key_exists((string) $sort, Collection::SORT_ORDERS) ? $sort : $collection->sort_order;
        $query = match ($sort) {
            'title' => $query->reorder()->orderBy('title'),
            'newest' => $query->reorder()->latest('products.created_at'),
            'price_asc', 'price_desc' => $query->reorder()->orderBy(
                ProductVariant::selectRaw('min(price)')->whereColumn('product_id', 'products.id'),
                $sort === 'price_asc' ? 'asc' : 'desc'
            ),
            default => $query,
        };
        $products = $query->with(['variants.image', 'images'])->paginate(24)->withQueryString();

        return view('shop.collection', compact('collection', 'products', 'sort'));
    }
}
