<?php

namespace EdLugz\Daraja\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use EdLugz\Daraja\Traits\HasUuid;

class MpesaTransaction extends Model
{
    use SoftDeletes;
    use HasUuid;

    protected $guarded = [];

}
