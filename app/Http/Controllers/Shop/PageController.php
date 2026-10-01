<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Page;

class PageController extends Controller
{
    public function show(string $handle)
    {
        $page = Page::where('handle', $handle)->where('is_published', true)->firstOrFail();

        return view('shop.page', compact('page'));
    }
}
