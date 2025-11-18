<?php

declare(strict_types=1);

namespace EdLugz\Daraja\Models;

use EdLugz\Daraja\Casts\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use EdLugz\Daraja\Traits\HasUuid;
use Illuminate\Support\Carbon;

/**
 * @property int id
 * @property string uuid
 * @property int fund_id
 * @property string mobile_no
 * @property float amount
 * @property string bill_reference
 * @property string|null merchant_request_id
 * @property string|null checkout_request_id
 * @property string|null response_code
 * @property string|null response_description
 * @property string|null result_code
 * @property string|null result_desc
 * @property string|null mpesa_receipt_number
 * @property string|null transaction_date
 * @property mixed|null json_request
 * @property mixed|null json_response
 * @property mixed|null json_result
 * @property Carbon|null created_at
 * @property Carbon|null updated_at
 * @property Carbon|null deleted_at
 */
class MpesaFunding extends Model
{
    use SoftDeletes;
    use HasUuid;

    protected $guarded = [];

    protected $casts = [
        'amount' => Money::class,
    ];

}
