<?php

namespace App\Exceptions;

use App\Enums\OrderStatus;
use DomainException;

class InvalidStatusTransitionException extends DomainException
{
    public function __construct(public readonly OrderStatus $from, public readonly OrderStatus $to)
    {
        parent::__construct("「{$from->label()}」の受注は「{$to->label()}」にできません");
    }
}
