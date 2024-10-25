<?php

namespace EdLugz\Daraja\Models;

use EdLugz\Daraja\Casts\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use EdLugz\Daraja\Traits\HasUuid;

/**
 * @property int id
 * @property string uuid
 * @property int|null account_id
 * @property string short_code
 * @property float utility_account
 * @property float working_account
 * @property float uncleared_balance
 * @property mixed|null json_result
 * @property \Illuminate\Support\Carbon|null created_at
 * @property \Illuminate\Support\Carbon|null updated_at
 * @property \Illuminate\Support\Carbon|null deleted_at
 */
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
