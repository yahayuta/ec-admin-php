<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

/**
 * Manual stock corrections (stocktaking, damaged goods, returns to the shelf).
 * A note is required so that every correction can be explained later.
 */
class StockAdjuster
{
    public function adjust(Product $product, int $change, string $note): Product
    {
        return DB::transaction(function () use ($product, $change, $note) {
            $product = Product::query()->whereKey($product->getKey())->lockForUpdate()->firstOrFail();

            if ($product->stock + $change < 0) {
                throw new InsufficientStockException([$product->sku]);
            }

            $product->stock += $change;
            $product->save();

            StockMovement::create([
                'product_id' => $product->id,
                'quantity_change' => $change,
                'stock_after' => $product->stock,
                'reason' => StockMovement::REASON_ADJUSTMENT,
                'note' => $note,
            ]);

            return $product;
        });
    }
}
