<?php

namespace EdLugz\Daraja\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApiCredential extends Model
{
    use SoftDeletes;

    protected $guarded = [];
}
