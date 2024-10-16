<?php

namespace EdLugz\Daraja\Casts;


use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Facades\Crypt;

class Encrypted implements CastsAttributes
{
    public function set($model, string $key, $value, array $attributes): string
    {
        return Crypt::encryptString($value);
    }

    public function get($model, string $key, $value, array $attributes) : string
    {
        return Crypt::decryptString($value);
    }
}
