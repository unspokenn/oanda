<?php

namespace Unspokenn\Oanda;

use Illuminate\Support\Facades\Facade as LaravelFacade;

/**
 * @see \Unspokenn\Oanda\Oanda
 */
class Facade extends LaravelFacade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'oanda';
    }
}
