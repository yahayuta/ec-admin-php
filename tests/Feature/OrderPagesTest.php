<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPagesTest extends TestCase
{
    use BuildsOrders;
    use RefreshDatabase;

    public function test_the_top_page_redirects_to_the_order_list(): void
    {
        $this->get('/')->assertRedirect('/orders');
    }

    public function test_the_order_list_can_be_filtered_by_status_channel_and_number(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $this->orderWith([[$product, 1]], OrderStatus::New, ['order_number' => 'ORD-100001', 'channel' => 'mall_a']);
        $this->orderWith([[$product, 1]], OrderStatus::Shipped, ['order_number' => 'ORD-100002', 'channel' => 'mall_b']);
        $this->orderWith([[$product, 1]], OrderStatus::New, ['order_number' => 'ORD-200003', 'channel' => 'own_site']);

        $this->get(route('orders.index'))
            ->assertOk()
            ->assertSee('ORD-100001')
            ->assertSee('ORD-100002')
            ->assertSee('ORD-200003');

        $this->get(route('orders.index', ['status' => 'shipped']))
            ->assertOk()
            ->assertSee('ORD-100002')
            ->assertDontSee('ORD-100001')
            ->assertDontSee('ORD-200003');

        $this->get(route('orders.index', ['channel' => 'own_site']))
            ->assertOk()
            ->assertSee('ORD-200003')
            ->assertDontSee('ORD-100001');

        $this->get(route('orders.index', ['q' => '1000']))
            ->assertOk()
            ->assertSee('ORD-100001')
            ->assertSee('ORD-100002')
            ->assertDontSee('ORD-200003');
    }

    public function test_unknown_filter_values_are_ignored(): void
    {
        $product = Product::factory()->create();
        $this->orderWith([[$product, 1]], OrderStatus::New, ['order_number' => 'ORD-300001']);

        $this->get(route('orders.index', ['status' => 'bogus', 'channel' => 'bogus']))
            ->assertOk()
            ->assertSee('ORD-300001');
    }

    public function test_the_order_page_shows_lines_and_only_the_allowed_actions(): void
    {
        $product = Product::factory()->create(['sku' => 'SKU-TEST-01', 'stock' => 7]);
        $order = $this->orderWith([[$product, 2]]);

        $this->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('SKU-TEST-01')
            ->assertSee('在庫を引き当てる')
            ->assertSee('キャンセルする')
            ->assertDontSee('出荷済みにする');
    }

    public function test_a_shipped_order_page_offers_no_actions(): void
    {
        $product = Product::factory()->create();
        $order = $this->orderWith([[$product, 1]], OrderStatus::Shipped);

        $this->get(route('orders.show', $order))
            ->assertOk()
            ->assertDontSee('在庫を引き当てる')
            ->assertDontSee('キャンセルする');
    }
}
