<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_number' => $this->faker->unique()->numerify('ORD-######'),
            'channel' => $this->faker->randomElement(array_keys(Order::CHANNELS)),
            'customer_name' => '架空 '.$this->faker->randomElement(['太郎', '花子', '一郎', '直子', '健太']),
            'status' => OrderStatus::New,
            'total_amount' => 0,
            'ordered_at' => $this->faker->dateTimeBetween('-30 days'),
        ];
    }
}
