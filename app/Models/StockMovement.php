<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only ledger: every change to Product::$stock writes one row here,
 * so the current stock can always be explained by its history.
 */
class StockMovement extends Model
{
    public const REASON_ALLOCATION = 'allocation';

    public const REASON_CANCELLATION = 'cancellation';

    public const REASON_ADJUSTMENT = 'adjustment';

    public const REASON_LABELS = [
        self::REASON_ALLOCATION => '引当',
        self::REASON_CANCELLATION => 'キャンセル戻し',
        self::REASON_ADJUSTMENT => '手動調整',
    ];

    protected $fillable = ['product_id', 'order_id', 'quantity_change', 'stock_after', 'reason', 'note'];

    protected function casts(): array
    {
        return [
            'quantity_change' => 'integer',
            'stock_after' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function reasonLabel(): string
    {
        return self::REASON_LABELS[$this->reason] ?? $this->reason;
    }
}
