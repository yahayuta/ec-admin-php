<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidStatusTransitionException;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Status changes for an order, together with the stock they move.
 *
 * Each method runs in one transaction and locks the order row first, so two
 * people pressing the same button at the same time cannot both succeed.
 * Product rows are locked in id order so that two orders sharing products
 * always wait for each other in the same order instead of deadlocking.
 */
class OrderWorkflow
{
    /**
     * Reserve stock for every line of the order. All or nothing: if any SKU is
     * short, nothing is reserved and the exception lists every short SKU.
     */
    public function allocate(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $order = $this->lockOrder($order);
            $this->assertCanMove($order, OrderStatus::Allocated);

            $needed = $this->quantitiesByProduct($order);
            $products = $this->lockProducts($needed->keys()->all());

            $short = $needed
                ->filter(fn (int $quantity, int $productId) => $products[$productId]->stock < $quantity)
                ->keys()
                ->map(fn (int $productId) => $products[$productId]->sku)
                ->values()
                ->all();

            if ($short !== []) {
                throw new InsufficientStockException($short);
            }

            foreach ($needed as $productId => $quantity) {
                $this->moveStock($products[$productId], -$quantity, StockMovement::REASON_ALLOCATION, $order);
            }

            $order->status = OrderStatus::Allocated;
            $order->save();

            return $order;
        });
    }

    /**
     * Cancel the order. Stock is returned only if it had been reserved.
     */
    public function cancel(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $order = $this->lockOrder($order);
            $this->assertCanMove($order, OrderStatus::Cancelled);

            if ($order->status === OrderStatus::Allocated) {
                $returned = $this->quantitiesByProduct($order);
                $products = $this->lockProducts($returned->keys()->all());

                foreach ($returned as $productId => $quantity) {
                    $this->moveStock($products[$productId], $quantity, StockMovement::REASON_CANCELLATION, $order);
                }
            }

            $order->status = OrderStatus::Cancelled;
            $order->save();

            return $order;
        });
    }

    /**
     * Mark a reserved order as shipped. Stock was already taken at allocation.
     */
    public function ship(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $order = $this->lockOrder($order);
            $this->assertCanMove($order, OrderStatus::Shipped);

            $order->status = OrderStatus::Shipped;
            $order->shipped_at = now();
            $order->save();

            return $order;
        });
    }

    private function lockOrder(Order $order): Order
    {
        return Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();
    }

    private function assertCanMove(Order $order, OrderStatus $next): void
    {
        if (! $order->status->canTransitionTo($next)) {
            throw new InvalidStatusTransitionException($order->status, $next);
        }
    }

    /**
     * The same product can appear on several lines, so add them up first.
     *
     * @return Collection<int, int> product id => quantity
     */
    private function quantitiesByProduct(Order $order): Collection
    {
        return $order->items()
            ->get(['product_id', 'quantity'])
            ->groupBy('product_id')
            ->map(fn (Collection $lines) => (int) $lines->sum('quantity'))
            ->sortKeys();
    }

    /**
     * @param  list<int>  $productIds
     * @return Collection<int, Product> keyed by id
     */
    private function lockProducts(array $productIds): Collection
    {
        return Product::query()
            ->whereKey($productIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    private function moveStock(Product $product, int $change, string $reason, Order $order): void
    {
        $product->stock += $change;
        $product->save();

        StockMovement::create([
            'product_id' => $product->id,
            'order_id' => $order->id,
            'quantity_change' => $change,
            'stock_after' => $product->stock,
            'reason' => $reason,
        ]);
    }
}
