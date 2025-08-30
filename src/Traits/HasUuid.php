<?php

declare(strict_types=1);

namespace EdLugz\Daraja\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @method static creating(\Closure $param)
 * @method static saving(\Closure $param)
 */
trait HasUuid
{
    /**
     * Override the route model binding to use uuid
     * to get the Model
     *
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Add behavior to creating and saving Eloquent events.
     * @return void
     */

    public static function bootHasUuid(): void
    {
        // Create a UUID to the model if it does not have one
        static::creating(function (Model $model) {

            if (!$model->uuid) {
                $model->uuid = (string)Str::uuid();
            }
        });

        // Set original if someone try to change UUID on update/save existing model
        static::saving(function (Model $model) {
            $originalId = $model->getOriginal('uuid');
            if (!is_null($originalId)) {
                if ($originalId !== $model->uuid) {
                    $model->uuid = $originalId;
                }
            }
        });
    }
}