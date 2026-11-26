<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Services\StockAdjuster;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $lowOnly = $request->boolean('low');

        $products = Product::query()
            ->when($lowOnly, fn ($query) => $query->lowStock())
            ->orderBy('sku')
            ->paginate(50)
            ->withQueryString();

        return view('products.index', [
            'products' => $products,
            'lowOnly' => $lowOnly,
        ]);
    }

    public function show(Product $product): View
    {
        $movements = $product->movements()
            ->with('order')
            ->latest('id')
            ->limit(50)
            ->get();

        return view('products.show', [
            'product' => $product,
            'movements' => $movements,
        ]);
    }

    public function adjust(Request $request, Product $product, StockAdjuster $adjuster): RedirectResponse
    {
        $validated = $request->validate([
            'change' => ['required', 'integer', 'not_in:0', 'between:-100000,100000'],
            'note' => ['required', 'string', 'max:200'],
        ], [
            'change.not_in' => '0 以外の数を入力してください',
            'note.required' => '調整の理由を入力してください',
        ]);

        try {
            $adjuster->adjust($product, (int) $validated['change'], $validated['note']);
        } catch (InsufficientStockException) {
            return redirect()
                ->route('products.show', $product)
                ->withErrors(['change' => '在庫がマイナスになる調整はできません'])
                ->withInput();
        }

        return redirect()->route('products.show', $product)->with('status', '在庫を調整しました');
    }
}
