<?php

declare(strict_types=1);

namespace EdLugz\Daraja\Facades;

use Illuminate\Support\Facades\Facade;

class Daraja extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'daraja';
    }
}
