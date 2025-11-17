<?php

namespace EdLugz\Daraja\Services;

use EdLugz\Daraja\Exceptions\MpesaChargeException;
use EdLugz\Daraja\Models\MpesaTransactionCharge;
use EdLugz\Daraja\Enums\MpesaTransactionChargeType;
use Illuminate\Support\Carbon;

class MpesaTransactionChargeService
{
    /**
     * Get applicable M-Pesa charge based on amount, type, and date.
     *
     * @param int $amount
     * @param MpesaTransactionChargeType $type
     * @param string|null $date
     * @return int
     * @throws MpesaChargeException
     */
    public static function getCharge(int $amount, MpesaTransactionChargeType $type, ?string $date = null): int
    {
        $date = $date ? Carbon::parse($date) : now();

        $charge = MpesaTransactionCharge::query()
            ->where('type', $type->value)
            ->where('effective_date', '<=', $date)
            ->where('min_amount', '<=', $amount)
            ->where(function ($q) use ($amount) {
                $q->where('max_amount', '>=', $amount)
                    ->orWhereNull('max_amount');
            })
            ->orderByDesc('effective_date')
            ->value('charge');

        if (is_null($charge)) {
            throw new MpesaChargeException("No M-Pesa charge found for Kshs. $amount as of {$date->toDateString()}");
        }

        return $charge;
    }
}