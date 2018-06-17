<?php

namespace Webplusm\LaraExtjs\Facades;

use Illuminate\Support\Facades\Facade;

class LaraExtjs extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'laraextjs';
    }
}
