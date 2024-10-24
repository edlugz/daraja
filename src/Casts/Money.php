<?php

namespace EdLugz\Daraja\Casts;

use App\Traits\HasMoneyConversion;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Casts value to cents and float
 * when setting and getting the value respectively
 */
class Money implements CastsAttributes
{
    use HasMoneyConversion;

    /**
     * Cast the given value.
     *
     * @param Model $model
     * @param string $key
     * @param mixed $value
     * @param array $attributes
     * @return float|null
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): float|null
    {
        if (is_null($value)) {
            return null;
        }
        return $this->fromCents($value);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param Model $model
     * @param string $key
     * @param mixed $value
     * @param array $attributes
     * @return int|null
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): int|null
    {
        if (is_null($value)) {
            return null;
        }
        return $this->toCents($value);
    }
}