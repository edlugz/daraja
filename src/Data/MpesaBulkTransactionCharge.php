<?php
/**
 * Created by PhpStorm
 * Filename: BulkTransactionCharge.php
 * User: henriquedn
 * Email: henrydkm@gmail.com
 * Datetime: 22/11/2025 Nov 2025 at 23:54
 */

namespace EdLugz\Daraja\Data;

readonly class MpesaBulkTransactionCharge {

    public function __construct(
        public float|int $utilityCharge = 0,
        public float|int $workingCharge = 0,
    )
    {
    }

    public function getTotalCharge(): float|int
    {
        return $this->utilityCharge + $this->workingCharge;
    }
}