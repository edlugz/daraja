<?php

namespace EdLugz\Daraja\database\seeders;

use Carbon\Carbon;
use EdLugz\Daraja\Enums\MpesaTransactionChargeType;
use EdLugz\Daraja\Models\MpesaTransactionCharge;
use Illuminate\Database\Seeder;

class MpesaTransactionChargeSeeder extends Seeder
{
    public function run(): void
    {
        $today = Carbon::today();

        // === Business Tariffs ===
        $businessCharges = [
            [0, 49, 2],
            [50, 100, 3],
            [101, 500, 8],
            [501, 1000, 13],
            [1001, 1500, 18],
            [1501, 2500, 25],
            [2501, 3500, 30],
            [3501, 5000, 39],
            [5001, 7500, 48],
            [7501, 10000, 54],
            [10001, 15000, 63],
            [15001, 20000, 68],
            [20001, 25000, 74],
            [25001, 30000, 79],
            [30001, 35000, 90],
            [35001, 40000, 106],
            [40001, 45000, 110],
            [45001, 1000000, 115],
        ];

        foreach ($businessCharges as [$min, $max, $fee]) {
            MpesaTransactionCharge::updateOrCreate(
                [
                    'type' => MpesaTransactionChargeType::BUSINESS->value,
                    'min_amount' => $min,
                    'max_amount' => $max,
                    'effective_date' => $today,
                ],
                ['charge' => $fee]
            );
        }

        // === Mobile Tariffs ===
        $mobileCharges = [
            [0, 100, 0],
            [101, 1500, 5],
            [1501, 5000, 9],
            [5001, 20000, 11],
            [20001, 250000, 13],
        ];

        foreach ($mobileCharges as [$min, $max, $fee]) {
            MpesaTransactionCharge::updateOrCreate(
                [
                    'type' => MpesaTransactionChargeType::MOBILE->value,
                    'min_amount' => $min,
                    'max_amount' => $max,
                    'effective_date' => $today,
                ],
                ['charge' => $fee]
            );
        }
    }
}
