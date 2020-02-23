<?php


namespace Palladiumlab\Core\Bitrix\Sale;


use Bitrix\Catalog\Product\Basket as CatalogBasket;
use Bitrix\Currency\CurrencyManager;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Sale\Basket as SaleBasket;
use Bitrix\Sale\BasketItem;
use Bitrix\Sale\Fuser;
use Bitrix\Sale\Order;
use Exception;
use Palladiumlab\Helpers\Catalog\Catalog;
use Palladiumlab\Templates\Singleton;

class Basket extends Singleton
{
    /** @var SaleBasket|null */
    protected $basket = null;

    protected function __construct()
    {
        Loader::includeModule('sale');
        $this->basket = SaleBasket::loadItemsForFUser(Fuser::getId(), bitrix_context()->getSite());
        parent::__construct();
    }

    /** @return Basket */
    public static function getInstance(): Singleton
    {
        return parent::getInstance();
    }

    public function getCurrent()
    {
        return $this->basket;
    }

    public function isItemExist(int $itemId)
    {
        return $this->getItemCount($itemId) > 0;
    }

    public function getCount()
    {
        return $this->basket->count();
    }

    public function getItemCount(int $itemId)
    {
        if ($item = $this->getExistItem($itemId)) {
            try {
                return (float)$item->getQuantity();
            } catch (Exception $e) {
                return 0;
            }
        }
        return 0;
    }

    public function getExistItem(int $itemId, string $moduleId = 'catalog')
    {
        try {
            /** @var BasketItem $basketItem */
            foreach ($this->basket->getBasketItems() as $basketItem) {
                if ((int)$basketItem->getField('PRODUCT_ID') === (int)$itemId
                    && $basketItem->getField('MODULE') === $moduleId) {
                    return $basketItem;
                }
            }
            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function getItems()
    {
        return $this->basket->getBasketItems();
    }

    public function basketDelete(int $itemId)
    {
        $result = new Result();
        try {
            if ($item = $this->getExistItem($itemId)) {
                $result = $item->delete();
                if ($result->isSuccess() && $this->basket->isChanged()) {
                    $result = $this->basket->save();
                }
            } else {
                $result->addError(new Error('Item not found!', 404));
            }
        } catch (Exception $e) {
            $result->addError(new Error($e->getMessage(), $e->getCode()));
        }

        return $result;
    }

    public function copy(Order $order)
    {
        $result = new Result();
        try {
            $products = [];
            /** @var $basketItem BasketItem */
            foreach ($order->getBasket()->getBasketItems() as $basketItem) {
                $products[(int)$basketItem->getProductId()] = (float)$basketItem->getQuantity();
            }
            $quantityList = Catalog::getAvailableQuantityList(array_keys($products));
            foreach ($products as $productId => $productQuantity) {
                if ($quantityList[$productId] > 0 && $productQuantity > $quantityList[$productId]) {
                    $productQuantity = $quantityList[$productId];
                }
                $setResult = $this->setQuantity($productId, $productQuantity);
                if (!$setResult->isSuccess()) {
                    /** @var $error Error */
                    foreach ($setResult->getErrorCollection() as $error) {
                        $result->addError($error);
                    }
                }
            }
        } catch (Exception $e) {
            $result->addError(new Error($e->getMessage(), $e->getCode()));
        }

        return $result;
    }

    public function setQuantity(int $itemId, $quantity)
    {
        $result = new Result();
        $quantity = (float)$quantity;
        try {
            if ($result->isSuccess()) {
                if (($item = $this->getExistItem($itemId)) && $quantity > 0) {
                    if ((float)$item->getQuantity() !== $quantity) {
                        $result = $this->setItemQuantity($item, $quantity);
                    }
                    if ($result->isSuccess() && $this->basket->isChanged()) {
                        $result = $this->basket->save();
                    }
                } else if ($quantity > 0) {
                    $result = $this->addItem($itemId, $quantity);
                    if ($result->isSuccess() && $this->basket->isChanged()) {
                        $result = $this->basket->save();
                    }
                } else {
                    $result->addError(new Error('Quantity less than zero!'));
                }
            }
        } catch (Exception $e) {
            $result->addError(new Error($e->getMessage(), $e->getCode()));
        }

        return $result;
    }

    protected function setItemQuantity(BasketItem $item, float $quantity)
    {
        $result = new Result();

        try {
            $result = $item->setField('QUANTITY', $quantity);
        } catch (Exception $e) {
            $result->addError(new Error($e->getMessage(), $e->getCode()));
        }
        return $result;
    }

    protected function addItem(int $itemId, $quantity)
    {
        $result = new Result();
        $quantity = (float)$quantity;
        try {
            $result = CatalogBasket::addProductToBasket($this->basket, [
                'PRODUCT_ID' => $itemId,
                'QUANTITY' => $quantity,
                'CURRENCY' => CurrencyManager::getBaseCurrency(),
            ], ['SITE_ID' => bitrix_context()->getSite()]);
        } catch (Exception $e) {
            $result->addError(new Error($e->getMessage(), $e->getCode()));
        }

        return $result;
    }

    public function clear()
    {
        try {
            $this->basket->clearCollection();
            return $this->basket->save();
        } catch (Exception $e) {
            return false;
        }
    }
}