<?php

declare(strict_types=1);

namespace EdLugz\Daraja\Traits;

trait HasMoneyConversion
{
    private function toCents(float $value): int
    {
        return intval(round($value * 100));
    }

    private function fromCents(int $value): float
    {
        return round($value / 100, 2);
    }
}