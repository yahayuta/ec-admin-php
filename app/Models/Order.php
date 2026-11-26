<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    public const CHANNELS = [
        'mall_a' => 'モールA',
        'mall_b' => 'モールB',
        'own_site' => '自社サイト',
    ];

    protected $fillable = [
        'order_number',
        'channel',
        'customer_name',
        'status',
        'total_amount',
        'ordered_at',
        'shipped_at',
    ];

    protected $attributes = [
        'status' => 'new',
        'total_amount' => 0,
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'total_amount' => 'integer',
            'ordered_at' => 'datetime',
            'shipped_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function channelLabel(): string
    {
        return self::CHANNELS[$this->channel] ?? $this->channel;
    }
}
