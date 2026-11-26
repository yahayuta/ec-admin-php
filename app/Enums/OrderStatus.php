<?php

namespace App\Enums;

enum OrderStatus: string
{
    case New = 'new';
    case Allocated = 'allocated';
    case Shipped = 'shipped';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::New => '新規',
            self::Allocated => '引当済み',
            self::Shipped => '出荷済み',
            self::Cancelled => 'キャンセル',
        };
    }

    /**
     * The only moves the business allows. Everything else is rejected in one place
     * instead of being checked ad hoc in each controller action.
     *
     * @return list<self>
     */
    public function allowedNext(): array
    {
        return match ($this) {
            self::New => [self::Allocated, self::Cancelled],
            self::Allocated => [self::Shipped, self::Cancelled],
            self::Shipped, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedNext(), true);
    }
}
