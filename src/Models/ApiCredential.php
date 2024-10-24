<?php

namespace EdLugz\Daraja\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use EdLugz\Daraja\Traits\HasUuid;

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
    ];
}
