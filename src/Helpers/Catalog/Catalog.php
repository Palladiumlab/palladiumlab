<?php


namespace Palladiumlab\Helpers\Catalog;


use Bitrix\Catalog\Model\Product;
use Bitrix\Main\Loader;
use Exception;

class Catalog
{
    public static function getAvailableQuantityList(array $productIdList)
    {
        $result = [];
        try {
            if (Loader::includeModule('catalog')) {
                $data = Product::getList([
                    'filter' => [
                        '=ID' => array_filter(array_map(function ($item) {
                            return (int)$item;
                        }, $productIdList))
                    ]
                ]);
                while ($product = $data->fetch()) {
                    $result[(int)$product['ID']] = (float)$product['QUANTITY'];
                }
            }
        } catch (Exception $e) {
            return $result;
        }
        return $result;
    }
}