<?php


namespace Palladiumlab\Core\Bitrix;


use Bitrix\Iblock\ElementTable;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Query\Result;
use Exception;
use Palladiumlab\Templates\Singleton;

/**
 * Class Search
 * @package Palladiumlab\Core
 */
class Search extends Singleton
{
    const CACHE_TIME = 60 * 60 * 24;
    public static $searchIblocks = [];
    /** @var Result|null */
    protected static $lastResult = null;

    /**
     * Search constructor.
     */
    protected function __construct()
    {
        Loader::includeModule('iblock');
        parent::__construct();
    }

    /** @return Search */
    public static function getInstance(): Singleton
    {
        return parent::getInstance();
    }

    /**
     * @param $query
     * @param float|int $cacheTime
     * @return Result|bool
     */
    public function query($query, $cacheTime = self::CACHE_TIME)
    {
        $query = trim((string)$query);
        if (!empty($query)) {
            try {
                $result = ElementTable::getList([
                    'filter' => [
                        '=IBLOCK_ID' => self::$searchIblocks,
                        '=ACTIVE' => 'Y',
                        '%SEARCHABLE_CONTENT' => [$query, mb_strtolower($query), mb_strtoupper($query)],
                    ],
                    'cache' => ['ttl' => (int)$cacheTime]
                ]);
                self::$lastResult = $result;
                return self::$lastResult;
            } catch (Exception $e) {
                return false;
            }
        }

        return false;
    }

    /**
     * @return array
     */
    public function getIdList()
    {
        if (self::$lastResult) {
            return array_column(self::$lastResult->fetchAll(), 'ID');
        } else {
            return [];
        }
    }
}