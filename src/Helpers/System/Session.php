<?php


namespace Palladiumlab\Helpers;


use Illuminate\Support\Arr;

class Session
{
    public static function get(string $key, $default = null)
    {
        return Arr::get($_SESSION, $key, $default);
    }

    public static function set(string $key, $value)
    {
        Arr::set($_SESSION, $key, $value);
    }

    public static function forget(string $key)
    {
        Arr::forget($_SESSION, $key);
    }
}