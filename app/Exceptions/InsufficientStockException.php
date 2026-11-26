<?php

namespace App\Exceptions;

use DomainException;

class InsufficientStockException extends DomainException
{
    /**
     * @param  list<string>  $skus
     */
    public function __construct(public readonly array $skus)
    {
        parent::__construct('在庫が足りません: '.implode(', ', $skus));
    }
}
