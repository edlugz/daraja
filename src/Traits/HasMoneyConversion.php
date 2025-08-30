<?php

declare(strict_types=1);

namespace EdLugz\Daraja\Traits;

trait HasMoneyConversion
{
    /**
     * From Float Currency to Cents
     *
     * Multiplies the value by 100 so that
     * it is stored as a big integer to avoid
     * floating points
     *
     * @param float $value
     * @return float
     */
    private function toCents(float $value): float
    {
        return ($value * 100);
    }

    /**
     * Cents currency to Float
     *
     * Multiplies the cents in biginteger by 0.01
     * so that currency is retrieved correct to 2 decimal places
     *
     * @param int $value
     * @return float
     */
    private function fromCents(int $value): float
    {
        return ($value * 0.01);
    }
}