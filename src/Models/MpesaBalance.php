<?php

namespace EdLugz\Daraja\Models;

use EdLugz\Daraja\Casts\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use EdLugz\Daraja\Traits\HasUuid;

class MpesaBalance extends Model
{
    use SoftDeletes;
    use HasUuid;
    protected $guarded = [];

    protected $casts = [
        'working_account' => Money::class,
        'utility_account' => Money::class,
        'uncleared_balance' => Money::class,
    ];

}
