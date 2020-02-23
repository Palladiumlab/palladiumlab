<?php


namespace Palladiumlab\Core\Bitrix\Iblock;


use ArrayAccess;
use Bitrix\Main\Data\TaggedCache;
use CIBlockElement;
use Illuminate\Support\Arr;
use Palladiumlab\Helpers\Bitrix\Cache\Cache;
use Palladiumlab\Helpers\Bitrix\Cache\Config;
use Palladiumlab\Helpers\Bitrix\Iblock\Iblock;
use Palladiumlab\Helpers\User;

/**
 * Class BaseList
 * @package Palladiumlab\Core\Bitrix\Iblock
 */
abstract class BaseList implements ArrayAccess
{
    protected const CACHE_TIME = 60 * 60 * 24;
    protected const CACHE_PATH = 'items/list';
    protected $list = [];

    public function __construct(array $list)
    {
        $this->list = $list;
    }

    public static function load(int $iblockId, array $elementIdList)
    {
        $elementIdList = array_map(function ($item) {
            return (int)$item;
        }, $elementIdList);
        $list = Cache::start(self::getCacheConfig($elementIdList),
            function (TaggedCache $taggedCache) use ($elementIdList, $iblockId) {
                $iblockHelper = Iblock::getInstance();
                $taggedCache->registerTag("iblock_id_{$iblockId}");
                $res = CIBlockElement::GetList([], [
                    'IBLOCK_ID' => $iblockId,
                    'ACTIVE' => 'Y',
                    'ID' => $elementIdList,
                ], false, false, $iblockHelper->getSelectFields($iblockId));

                return $iblockHelper->resultToArray($res, function ($resultItem) {
                    return static::modifyResultItem($resultItem);
                });
            }
        ) ?: [];

        return new static($list);
    }

    protected static function getCacheConfig(array $filter)
    {
        $cacheTime = static::CACHE_TIME;
        $cachePath = static::CACHE_PATH;
        $cacheKey = md5($cachePath . serialize($filter));
        return new Config($cacheTime, $cacheKey, $cachePath);
    }

    protected static function modifyResultItem($element)
    {
        return $element;
    }

    public function getItem(string $key, $default = null)
    {
        return Arr::get($this->list, $key, $default);
    }

    public function toArray()
    {
        return $this->list;
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($key)
    {
        return Arr::exists($this->list, $key);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($key)
    {
        return Arr::get($this->list, $key);
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($key, $value)
    {
        Arr::set($this->list, $key, $value);
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($key)
    {
        Arr::forget($this->list, $key);
    }
}