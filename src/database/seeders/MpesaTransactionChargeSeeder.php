<?php

namespace EdLugz\Daraja\database\seeders;

use Carbon\Carbon;
use EdLugz\Daraja\Enums\MpesaTransactionChargeType;
use EdLugz\Daraja\Enums\MpesaTransactionType;
use EdLugz\Daraja\Models\MpesaTransactionCharge;
use Illuminate\Database\Seeder;

class MpesaTransactionChargeSeeder extends Seeder
{
    public function run(): void
    {
        $today = Carbon::today();

        // === Business Tariffs ===
        $businessCharges = [
            [0, 4900, 200],
            [5000, 10000, 300],
            [10100, 50000, 800],
            [50100, 100000, 1300],
            [100100, 150000, 1800],
            [150100, 250000, 2500],
            [250100, 350000, 3000],
            [350100, 500000, 3900],
            [500100, 750000, 4800],
            [750100, 1000000, 5400],
            [1000100, 1500000, 6300],
            [1500100, 2000000, 6800],
            [2000100, 2500000, 7400],
            [2500100, 3000000, 7900],
            [3000100, 3500000, 9000],
            [3500100, 4000000, 10600],
            [4000100, 4500000, 11000],
            [4500100, 100000000, 11500],
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
            [0, 10000, 0],
            [10100, 150000, 500],
            [150100, 500000, 900],
            [500100, 2000000, 1100],
            [2000100, 25000000, 1300],
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
