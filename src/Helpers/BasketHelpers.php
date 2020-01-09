<?php


namespace Palladiumlab\Helpers;


use Bitrix\Currency\CurrencyManager;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Sale\Basket;
use Bitrix\Sale\BasketItem;
use Bitrix\Sale\Fuser;
use CCatalogSku;
use Exception;

class BasketHelpers
{
    const DEFAULT_QUANTITY = 1;

    /** @var $basket Basket|null */
    private static $basket = null;

    public static function productInBasket(int $productId): bool
    {
        self::init();

        /** @var BasketItem $basketItem */
        foreach (self::$basket as $basketItem) {
            if ($basketItem->getProductId() == $productId) {
                return true;
            }
        }

        return false;
    }

    private static function init()
    {
        if (!self::$basket) {
            Loader::includeModule('sale');
            self::$basket = Basket::loadItemsForFUser(Fuser::getId(), context()->getSite());
        }
    }

    public static function clear(): bool
    {
        self::init();

        $isAllSuccess = true;
        /** @var BasketItem $basketItem */
        foreach (self::$basket as $basketItem) {
            try {
                $result = $basketItem->delete();
            } catch (Exception $e) {
                return false;
            }
            if (!$result->isSuccess()) {
                $isAllSuccess = false;
            }
        }
        if ($isAllSuccess) {
            self::$basket->save();
        }

        return $isAllSuccess;
    }

    public static function getCount(): int
    {
        self::init();
        return (int)self::$basket->count();
    }

    public static function addProduct(int $offerId): bool
    {
        self::init();
        $productId = (int)CCatalogSku::GetProductInfo($offerId, IBLOCK_1C_CATALOG_TP_ID)['ID'];
        if ($productId <= 0) {
            return  false;
        }
        $quantity = self::DEFAULT_QUANTITY;

        if ($item = self::$basket->getExistsItem('catalog', $productId)) {
            $result = $item->setField('QUANTITY', $item->getQuantity() + $quantity);
        } else {
            $item = self::$basket->createItem('catalog', $productId);
            $result = $item->setFields([
                'QUANTITY' => $quantity,
                'CURRENCY' => CurrencyManager::getBaseCurrency(),
                'LID' => \context()->getSite(),
                'PRODUCT_PROVIDER_CLASS' => \Bitrix\Catalog\Product\Basket::getDefaultProviderName(),
            ]);
        }

        self::$basket->save();

        return $result->isSuccess();
    }

    public static function deleteProduct(int $productId): bool
    {
        self::init();

        $result = true;

        if ($item = self::$basket->getExistsItem('catalog', $productId)) {
            $result = $item->delete();
        }

        self::$basket->save();

        return $result;
    }

    public static function getIds(): array
    {
        self::init();
        $result = [];

        /** @var BasketItem $basketItem */
        foreach (self::$basket as $basketItem) {
            $result[] = (int)$basketItem->getProductId();
        }

        return $result;
    }

    public static function getNames()
    {
        self::init();
        $result = [];

        /** @var BasketItem $basketItem */
        foreach (self::$basket as $basketItem) {
            $result[] = $basketItem->getField('NAME');
        }

        return $result;
    }
}