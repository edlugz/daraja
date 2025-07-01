<?php

namespace EdLugz\Daraja\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use EdLugz\Daraja\Traits\HasUuid;

/**
 * @property  int id
 * @property  string uuid
 * @property  int|null account_id
 * @property  string|null short_code
 * @property  string|null initiator
 * @property  string|null initiator_password
 * @property  string|null pass_key
 * @property  string|null consumer_key
 * @property  string|null consumer_secret
 * @property  string|null balance_result_url
 * @property  bool api_status
 * @property  bool use_b2c_validation
 * @property  \Illuminate\Support\Carbon|null created_at
 * @property  \Illuminate\Support\Carbon|null updated_at
 * @property  \Illuminate\Support\Carbon|null deleted_at
 */
class ApiCredential extends Model
{
    use SoftDeletes;
    use HasUuid;

    protected $guarded = [];

    protected $casts = [
        'initiator_password' => 'encrypted',
        'pass_key' => 'encrypted',
        'consumer_key' => 'encrypted',
        'consumer_secret' => 'encrypted',
        'api_status' => 'boolean',
        'use_b2c_validation' => 'boolean'
    ];
}
