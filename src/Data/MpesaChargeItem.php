<?php

declare(strict_types=1);

namespace EdLugz\Daraja\Data;

use EdLugz\Daraja\Enums\MpesaTransactionChargeType;

readonly class MpesaChargeItem
{
    /**
     * @param int $amount
     * @param MpesaTransactionChargeType $type
     */
    public function __construct(
        public int $amount,
        public MpesaTransactionChargeType $type,
    ) {}
}