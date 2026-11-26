<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\OrderWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderWorkflowTest extends TestCase
{
    use BuildsOrders;
    use RefreshDatabase;

    public function test_allocating_reserves_stock_for_every_line_and_records_movements(): void
    {
        $case = Product::factory()->create(['stock' => 10]);
        $film = Product::factory()->create(['stock' => 5]);
        // The same product on two lines must be added up before checking stock.
        $order = $this->orderWith([[$case, 3], [$film, 5], [$case, 2]]);

        $this->post(route('orders.allocate', $order))
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHasNoErrors();

        $this->assertSame(5, $case->fresh()->stock);
        $this->assertSame(0, $film->fresh()->stock);
        $this->assertSame(OrderStatus::Allocated, $order->fresh()->status);
        $this->assertDatabaseCount('stock_movements', 2);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $case->id,
            'order_id' => $order->id,
            'quantity_change' => -5,
            'stock_after' => 5,
            'reason' => StockMovement::REASON_ALLOCATION,
        ]);
    }

    public function test_allocation_is_all_or_nothing_when_any_sku_is_short(): void
    {
        $enough = Product::factory()->create(['stock' => 10]);
        $short = Product::factory()->create(['stock' => 1]);
        $order = $this->orderWith([[$enough, 3], [$short, 2]]);

        $this->post(route('orders.allocate', $order))
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHasErrors('order');

        $this->assertStringContainsString($short->sku, session('errors')->first('order'));
        $this->assertSame(10, $enough->fresh()->stock);
        $this->assertSame(1, $short->fresh()->stock);
        $this->assertSame(OrderStatus::New, $order->fresh()->status);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_an_order_cannot_be_allocated_twice(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $order = $this->orderWith([[$product, 4]]);

        $this->post(route('orders.allocate', $order))->assertSessionHasNoErrors();
        $this->post(route('orders.allocate', $order))->assertSessionHasErrors('order');

        $this->assertSame(6, $product->fresh()->stock);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_cancelling_an_allocated_order_returns_the_stock(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $order = $this->orderWith([[$product, 4]]);
        app(OrderWorkflow::class)->allocate($order);

        $this->post(route('orders.cancel', $order))->assertSessionHasNoErrors();

        $this->assertSame(10, $product->fresh()->stock);
        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'order_id' => $order->id,
            'quantity_change' => 4,
            'stock_after' => 10,
            'reason' => StockMovement::REASON_CANCELLATION,
        ]);
    }

    public function test_cancelling_a_new_order_leaves_stock_alone(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $order = $this->orderWith([[$product, 4]]);

        $this->post(route('orders.cancel', $order))->assertSessionHasNoErrors();

        $this->assertSame(10, $product->fresh()->stock);
        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_a_shipped_order_cannot_be_cancelled(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $order = $this->orderWith([[$product, 1]], OrderStatus::Shipped);

        $this->post(route('orders.cancel', $order))->assertSessionHasErrors('order');

        $this->assertSame(OrderStatus::Shipped, $order->fresh()->status);
        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_an_order_must_be_allocated_before_it_ships(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $order = $this->orderWith([[$product, 1]]);

        $this->post(route('orders.ship', $order))->assertSessionHasErrors('order');

        $this->assertSame(OrderStatus::New, $order->fresh()->status);
        $this->assertNull($order->fresh()->shipped_at);
    }

    public function test_shipping_an_allocated_order_records_the_time(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $order = $this->orderWith([[$product, 1]]);
        app(OrderWorkflow::class)->allocate($order);

        $this->post(route('orders.ship', $order))->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame(OrderStatus::Shipped, $order->status);
        $this->assertNotNull($order->shipped_at);
        // Shipping does not touch stock again; it was taken at allocation.
        $this->assertSame(9, $product->fresh()->stock);
    }
}
