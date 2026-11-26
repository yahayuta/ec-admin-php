<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use PHPUnit\Framework\TestCase;

class OrderStatusTest extends TestCase
{
    public function test_allowed_transitions(): void
    {
        $this->assertTrue(OrderStatus::New->canTransitionTo(OrderStatus::Allocated));
        $this->assertTrue(OrderStatus::New->canTransitionTo(OrderStatus::Cancelled));
        $this->assertTrue(OrderStatus::Allocated->canTransitionTo(OrderStatus::Shipped));
        $this->assertTrue(OrderStatus::Allocated->canTransitionTo(OrderStatus::Cancelled));
    }

    public function test_rejected_transitions(): void
    {
        $this->assertFalse(OrderStatus::New->canTransitionTo(OrderStatus::Shipped));
        $this->assertFalse(OrderStatus::Allocated->canTransitionTo(OrderStatus::New));
        $this->assertFalse(OrderStatus::Shipped->canTransitionTo(OrderStatus::Cancelled));
        $this->assertFalse(OrderStatus::Cancelled->canTransitionTo(OrderStatus::Allocated));
    }

    public function test_finished_orders_have_no_next_step(): void
    {
        $this->assertSame([], OrderStatus::Shipped->allowedNext());
        $this->assertSame([], OrderStatus::Cancelled->allowedNext());
    }
}
