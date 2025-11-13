<?php

namespace EdLugz\Daraja\Models;

use EdLugz\Daraja\Casts\Money;
use Illuminate\Database\Eloquent\Model;
use EdLugz\Daraja\Enums\MpesaTransactionChargeType;

class MpesaTransactionCharge extends Model
{
    protected $fillable = [
        'type',
        'min_amount',
        'max_amount',
        'charge',
        'effective_date',
    ];

    protected $casts = [
        'min_amount' => Money::class,
        'max_amount' => Money::class,
        'charge' => Money::class,
        'effective_date' => 'date',
        'type' => MpesaTransactionChargeType::class,
    ];
}