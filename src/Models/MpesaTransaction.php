<?php

namespace EdLugz\Daraja\Models;

use EdLugz\Daraja\Casts\Money;
use EdLugz\Daraja\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int id
 * @property string uuid
 * @property int payment_id
 * @property string payment_reference
 * @property string short_code
 * @property string transaction_type
 * @property string account_number
 * @property float amount
 * @property string|null bill_reference
 * @property string|null requester_name
 * @property string|null requester_mobile
 * @property string|null conversation_id
 * @property string|null originator_conversation_id
 * @property string|null response_code
 * @property string|null response_description
 * @property string|null result_type
 * @property string|null result_code
 * @property string|null result_description
 * @property string|null transaction_id
 * @property string|null transaction_status
 * @property \Illuminate\Support\Carbon|null transaction_completed_date_time
 * @property string|null receiver_party_public_name
 * @property float|null utility_account_balance
 * @property float|null working_account_balance
 * @property mixed|null json_request
 * @property mixed|null json_response
 * @property mixed|null json_result
 * @property \Illuminate\Support\Carbon|null created_at
 * @property \Illuminate\Support\Carbon|null updated_at
 * @property \Illuminate\Support\Carbon|null deleted_at
 */
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
