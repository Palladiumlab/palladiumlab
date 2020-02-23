<?php


namespace Palladiumlab\Helpers\Bitrix\Iblock;


use Bitrix\Iblock\ElementTable;
use Bitrix\Main\Data\TaggedCache;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Palladiumlab\Helpers\Bitrix\Cache\Cache;
use Palladiumlab\Helpers\Bitrix\Cache\Config;
use Palladiumlab\Templates\Singleton;
use Palladiumlab\Traits\StaticCacheTrait;

class Element extends Singleton
{
    use StaticCacheTrait;

    /**
     * @return Element
     */
    public static function getInstance(): Singleton
    {
        return parent::getInstance();
    }

    public function setProperties(int $iblockId, int $elementId, array $fields)
    {
        $result = new Result();
        if (!$this->isExist(['=ID' => $elementId])) {
            $result->addError(new Error('Object does not exist'));
        }
        if (empty($fields)) {
            $result->addError(new Error('Empty fields'));
        }

        if ($result->isSuccess()) {
            CIBlockElement::SetPropertyValuesEx($elementId, $iblockId, $fields);
            CIBlock::clearIblockTagCache($iblockId);
            $this->clearStatic();
        }

        return $result;
    }

    public function isExist(array $filter)
    {
        try {
            return ElementTable::getCount($filter, ['ttl' => 60 * 60 * 24]) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getCount(array $filter, int $cache = 60 * 60 * 24)
    {
        $key = md5(serialize($filter));
        $config = new Config($cache, $key, 'iblock/element_count');
        if (($fromStatic = self::getStatic($key)) && $cache > 0) {
            return $fromStatic;
        }
        $count = (int)Cache::start($config, function (TaggedCache $taggedCache, Config $config) use ($filter) {
            $count = (int)CIBlockElement::GetList([], $filter, [], false, []);
            if ($filter['IBLOCK_ID'] > 0) {
                $taggedCache->registerTag("iblock_id_{$filter['IBLOCK_ID']}");
            }
            return $count;
        });
        if ($count && $cache > 0) {
            self::setStatic($key, $count);
        }

        return $count;
    }

    public function getPropertyValue(int $iblockId, int $elementId, string $propertyCode, $default = null, int $cache = 60 * 60 * 24)
    {
        $key = md5(serialize([$iblockId, $elementId, $propertyCode]));
        $config = new Config($cache, $key, 'iblock/element_property_values');
        if (($fromStatic = self::getStatic($key)) && $cache > 0) {
            return $fromStatic;
        }
        $propertyValue = Cache::start($config,
            function (TaggedCache $taggedCache) use ($iblockId, $elementId, $propertyCode) {
                $taggedCache->registerTag("iblock_id_{$iblockId}");
                return CIBlockElement::GetProperty(
                    $iblockId,
                    $elementId,
                    false,
                    false,
                    ['CODE' => $propertyCode]
                )->Fetch()['VALUE'];
            }
        );
        if ($propertyValue && $cache > 0) {
            self::setStatic($key, $propertyValue);
        }

        return $propertyValue ?: $default;
    }
}
