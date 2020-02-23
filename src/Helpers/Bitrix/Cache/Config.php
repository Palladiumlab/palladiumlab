<?php


namespace Palladiumlab\Helpers\Bitrix\Cache;


/**
 * Class Config
 * @package Palladiumlab\Core\Cache
 */
class Config
{
    /** @var int */
    protected $cacheTime;
    /** @var string */
    protected $cacheKey;
    /** @var string */
    protected $cachePath;

    public function __construct(int $time, string $key, string $path)
    {
        $this->cacheTime = $time;
        $this->cacheKey = $key;
        $this->cachePath = $path;
    }

    public function getTime()
    {
        return $this->cacheTime;
    }

    public function getKey()
    {
        return $this->cacheKey;
    }

    public function getPath()
    {
        return $this->cachePath;
    }
}
