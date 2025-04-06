<?php

namespace FelixFrey\LaravelTheme\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @mixin \FelixFrey\LaravelTheme\ThemeLoader
 *
 * @see \FelixFrey\LaravelTheme\ThemeLoader
 */
class ThemeLoader extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'theme-loader';
    }
}
