<?php

namespace EdLugz\Daraja\Models;

use EdLugz\Daraja\Casts\Money;
use EdLugz\Daraja\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MpesaTransaction extends Model
{
    use SoftDeletes;
    use HasUuid;

    protected $guarded = [];

    protected $casts = [
        'amount' => Money::class,
        'utility_account_balance' => Money::class,
        'working_account_balance' => Money::class,
    ];

}
