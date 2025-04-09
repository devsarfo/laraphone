<?php

namespace DevSarfo\LaraPhone\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \DevSarfo\LaraPhone\LaraPhone
 */
class LaraPhone extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \DevSarfo\LaraPhone\LaraPhone::class;
    }
}
