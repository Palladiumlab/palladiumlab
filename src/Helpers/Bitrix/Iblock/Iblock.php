<?php


namespace Palladiumlab\Helpers\Bitrix\Iblock;


use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Loader;
use CDBResult;
use Exception;
use Palladiumlab\Templates\Singleton;
use Palladiumlab\Traits\StaticCacheTrait;

/**
 * Class Iblock
 * @package Palladiumlab\Helpers
 */
class Iblock extends Singleton
{
    use StaticCacheTrait;

    /**
     * @return Iblock
     */
    public static function getInstance(): Singleton
    {
        return parent::getInstance();
    }

    public function getSelectFields(int $iblockId)
    {
        $propertyCodeList = array_map(function ($code) {
            return "PROPERTY_{$code}";
        }, array_pluck($this->getPropertyList($iblockId), 'CODE'));
        return array_merge([
            'ID', 'NAME', 'CODE', 'DETAIL_PAGE_URL', 'ACTIVE',
        ], $propertyCodeList);
    }

    public function getPropertyList(int $iblockId, $cache = 60 * 60 * 24)
    {
        try {
            if (Loader::includeModule('iblock')) {
                if (($fromStatic = $this->getStatic("propertyList.{$iblockId}")) && $cache > 0) {
                    return $fromStatic;
                }
                $fromTable = PropertyTable::getList([
                    'filter' => ['=IBLOCK_ID' => $iblockId],
                    'cache' => ['ttl' => $cache],
                ])->fetchAll();
                if ($cache > 0) {
                    $this->setStatic("propertyList.{$iblockId}", $fromTable);
                }

                return $fromTable;
            }
        } catch (Exception $e) {
            return [];
        }
        return [];
    }

    public function resultToArray(CDBResult $result, $callback = null)
    {
        $array = [];
        while ($arrayItem = $result->Fetch()) {
            $itemId = (int)$arrayItem['ID'];
            if (is_callable($callback)) {
                $arrayItem = $callback($arrayItem);
            }

            if ($itemId > 0) {
                $array[$itemId] = $arrayItem;
            } else {
                $array[] = $arrayItem;
            }
        }
        return $array;
    }

    public function getPropertyId(int $iblockId, string $code, $cache = 60 * 60 * 24)
    {
        try {
            if (Loader::includeModule('iblock')) {
                if (($fromStatic = $this->getStatic("{$iblockId}.{$code}")) && $cache > 0) {
                    return $fromStatic;
                }
                $fromTable = (int)PropertyTable::getRow([
                    'filter' => [
                        '=IBLOCK_ID' => $iblockId,
                        '=CODE' => $code,
                    ],
                    'select' => ['ID'],
                    'cache' => ['ttl' => $cache]
                ])['ID'];
                if ($cache > 0) {
                    $this->setStatic("{$iblockId}.{$code}", $fromTable);
                }

                return $fromTable;
            }
        } catch (Exception $e) {
            return 0;
        }
        return 0;
    }
}