<?php


namespace Palladiumlab\Traits;


use Illuminate\Support\Arr;

/**
 * Trait StaticCacheTrait
 * @package Palladiumlab\Traits
 */
trait StaticCacheTrait
{
    /** @var array */
    protected static $cache = [];

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getStatic(string $key, $default = null)
    {
        return Arr::get(static::$cache, $key, $default);
    }

    /**
     * @param array $keys
     * @return StaticCacheTrait
     */
    public function forgetStatic(array $keys)
    {
        Arr::forget(static::$cache, $keys);
        return $this;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return StaticCacheTrait
     */
    public function setStatic(string $key, $value)
    {
        Arr::set(static::$cache, $key, $value);
        return $this;
    }

    public function clearStatic()
    {
        self::$cache = [];
    }
}
