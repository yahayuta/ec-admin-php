<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;

trait BuildsOrders
{
    /**
     * @param  list<array{0: Product, 1: int}>  $lines  [product, quantity]
     */
    protected function orderWith(array $lines, OrderStatus $status = OrderStatus::New, array $attributes = []): Order
    {
        $order = Order::factory()->create(['status' => $status] + $attributes);

        foreach ($lines as [$product, $quantity]) {
            OrderItem::factory()->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => 1000,
            ]);
        }

        $order->update(['total_amount' => 1000 * array_sum(array_column($lines, 1))]);

        return $order;
    }
}
