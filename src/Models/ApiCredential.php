<?php

namespace EdLugz\Daraja\Models;

use Ramsey\Uuid\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use EdLugz\Daraja\Casts\Encrypted;

class ApiCredential extends Model
{

    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'initiator_password' => Encrypted::class,
    ];

    public function newUniqueId(): string
    {
        return (string) Uuid::uuid4();
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }
}

