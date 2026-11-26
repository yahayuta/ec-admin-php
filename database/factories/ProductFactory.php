<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    private const NAMES = [
        'スマホケース', '保護フィルム', '充電ケーブル', 'モバイルバッテリー', 'スマホスタンド',
        'イヤホン', 'タブレットケース', 'ワイヤレス充電器', 'カメラレンズ保護', 'ストラップ',
    ];

    public function definition(): array
    {
        return [
            'sku' => $this->faker->unique()->bothify('SKU-####-??'),
            'name' => $this->faker->randomElement(self::NAMES).' '.$this->faker->randomElement(['ブラック', 'ホワイト', 'ネイビー', 'クリア']),
            'stock' => $this->faker->numberBetween(0, 60),
            'reorder_point' => $this->faker->randomElement([5, 10, 20]),
        ];
    }
}
