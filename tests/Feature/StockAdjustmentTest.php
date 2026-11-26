<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_can_be_adjusted_with_a_reason(): void
    {
        $product = Product::factory()->create(['stock' => 3]);

        $this->post(route('products.adjust', $product), ['change' => 5, 'note' => '棚卸しで見つかった分'])
            ->assertRedirect(route('products.show', $product))
            ->assertSessionHasNoErrors();

        $this->assertSame(8, $product->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'order_id' => null,
            'quantity_change' => 5,
            'stock_after' => 8,
            'reason' => StockMovement::REASON_ADJUSTMENT,
            'note' => '棚卸しで見つかった分',
        ]);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('棚卸しで見つかった分');
    }

    public function test_an_adjustment_cannot_make_stock_negative(): void
    {
        $product = Product::factory()->create(['stock' => 2]);

        $this->post(route('products.adjust', $product), ['change' => -3, 'note' => '破損'])
            ->assertSessionHasErrors('change');

        $this->assertSame(2, $product->fresh()->stock);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_an_adjustment_needs_a_non_zero_change_and_a_reason(): void
    {
        $product = Product::factory()->create(['stock' => 2]);

        $this->post(route('products.adjust', $product), ['change' => 0, 'note' => ''])
            ->assertSessionHasErrors(['change', 'note']);

        $this->assertSame(2, $product->fresh()->stock);
    }

    public function test_the_list_can_show_only_products_at_or_below_the_reorder_point(): void
    {
        Product::factory()->create(['sku' => 'SKU-LOW-01', 'stock' => 5, 'reorder_point' => 5]);
        Product::factory()->create(['sku' => 'SKU-OK-01', 'stock' => 6, 'reorder_point' => 5]);

        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee('SKU-LOW-01')
            ->assertSee('SKU-OK-01');

        $this->get(route('products.index', ['low' => 1]))
            ->assertOk()
            ->assertSee('SKU-LOW-01')
            ->assertDontSee('SKU-OK-01');
    }
}
