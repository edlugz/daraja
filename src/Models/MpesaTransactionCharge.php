<?php

namespace EdLugz\Daraja\Models;

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
        'effective_date' => 'date',
        'type' => MpesaTransactionChargeType::class,
    ];
}