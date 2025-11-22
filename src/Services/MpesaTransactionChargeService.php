<?php

namespace EdLugz\Daraja\Services;

use EdLugz\Daraja\Data\MpesaChargeItem;
use EdLugz\Daraja\Exceptions\MpesaChargeException;
use EdLugz\Daraja\Models\MpesaTransactionCharge;
use EdLugz\Daraja\Enums\MpesaTransactionChargeType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

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
    public static function getSingleTransactionCharge(int $amount, MpesaTransactionChargeType $type, ?string $date = null): int
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

    public static function getBulkTransactionCharges(
        Collection $items,
        ?string $date = null
    ): Collection {

        $date = $date ? Carbon::parse($date) : now();
        
        $grouped = $items->groupBy(fn (MpesaChargeItem $item) => $item->type->value);

        $bandsByType = [];

        foreach ($grouped as $typeValue => $collection) {

            $bands = MpesaTransactionCharge::query()
                ->where('type', $typeValue)
                ->where('effective_date', '<=', $date)
                ->orderByDesc('effective_date')
                ->orderBy('min_amount')
                ->get()
                ->unique('min_amount');

            $ranges = [];

            foreach ($bands as $b) {
                $ranges[] = [
                    'min' => (int) $b->min_amount,
                    'max' => $b->max_amount ? (int) $b->max_amount : null,
                    'charge' => (int) $b->charge,
                    'effective_date' => $b->effective_date,
                ];
            }

            // Sort ascending by min amount for binary search
            usort($ranges, fn ($a, $b) => $a['min'] <=> $b['min']);

            $bandsByType[$typeValue] = $ranges;
        }

        // search
        $binarySearch = function (int $amount, array $ranges) {
            $low = 0;
            $high = count($ranges) - 1;

            while ($low <= $high) {
                $mid = intdiv($low + $high, 2);
                $band = $ranges[$mid];

                if ($amount < $band['min']) {
                    $high = $mid - 1;
                } elseif (!is_null($band['max']) && $amount > $band['max']) {
                    $low = $mid + 1;
                } else {
                    return $band; // FOUND
                }
            }

            return null;
        };

        // collection
        return $items->map(function (MpesaChargeItem $item) use ($bandsByType, $binarySearch, $date) {

            $type = $item->type->value;
            $amount = $item->amount;

            $ranges = $bandsByType[$type];

            $band = $binarySearch($amount, $ranges);

            if (!$band) {
                throw new MpesaChargeException(
                    "No M-Pesa charge found for Kshs. {$amount} ({$type}) as of {$date->toDateString()}"
                );
            }

            return collect([
                'amount' => $amount,
                'type' => $type,
                'charge' => $band['charge'],
                'band_min' => $band['min'],
                'band_max' => $band['max'],
                'effective_date' => $band['effective_date'],
            ]);
        });
    }

}