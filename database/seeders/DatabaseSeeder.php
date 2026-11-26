<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Fictional demo data only: 30 products and 80 new orders across three channels.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::factory()->count(30)->create();

        Order::factory()->count(80)->create()->each(function (Order $order) use ($products) {
            $lines = $products->random(random_int(1, 3));

            foreach ($lines as $product) {
                OrderItem::factory()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                ]);
            }

            $order->update([
                'total_amount' => (int) $order->items()->get()->sum(fn (OrderItem $item) => $item->subtotal()),
            ]);
        });
    }
}
