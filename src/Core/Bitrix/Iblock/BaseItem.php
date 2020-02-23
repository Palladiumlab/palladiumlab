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
 * Class BaseItem
 * @package Palladiumlab\Core\Bitrix\Iblock
 */
abstract class BaseItem implements ArrayAccess
{
    protected const CACHE_TIME = 60 * 60 * 24;
    protected const CACHE_PATH = 'items/item_#IBLOCK_ID#';
    protected const IBLOCK_ID = 0;

    /** @var array */
    protected $fields = [];

    public function __construct($fields)
    {
        $this->fields = $fields;
    }

    public static function load(int $elementId = 0)
    {
        $filter = [
            'IBLOCK_ID' => static::IBLOCK_ID,
            'ACTIVE' => 'Y',
        ];
        if ($elementId > 0) {
            $filter['ID'] = $elementId;
        }

        $fields = Cache::start(static::getCacheConfig($filter, $elementId),
            function (TaggedCache $taggedCache) use ($filter) {
                $iblockHelper = Iblock::getInstance();
                $iblockId = static::IBLOCK_ID;
                $taggedCache->registerTag("iblock_id_{$iblockId}");
                return CIBlockElement::GetList(
                    [],
                    $filter,
                    false,
                    false,
                    $iblockHelper->getSelectFields($iblockId)
                )->Fetch();
            }
        ) ?: [];

        return new static($fields);
    }

    protected static function getCacheConfig(array $filter, int $elementId = 0, string $key = '')
    {
        $cacheTime = static::CACHE_TIME;
        $cachePath = str_replace(
            [
                '#IBLOCK_ID#',
                '#ELEMENT_ID#',
            ],
            [
                static::IBLOCK_ID,
                $elementId,
            ],
            static::CACHE_PATH
        );
        $cacheKey = md5($cachePath . serialize($filter)) . $key;
        return new Config($cacheTime, $cacheKey, $cachePath);
    }

    public function getField(string $key, $default = null)
    {
        return Arr::get($this->fields, $key, $default);
    }

    public function toArray()
    {
        return $this->fields;
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($key)
    {
        return Arr::exists($this->fields, $key);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($key)
    {
        return Arr::get($this->fields, $key);
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($key, $value)
    {
        Arr::set($this->fields, $key, $value);
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($key)
    {
        Arr::forget($this->fields, $key);
    }
}