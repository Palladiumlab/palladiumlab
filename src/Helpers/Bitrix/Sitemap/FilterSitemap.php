<?php


namespace Palladiumlab\Helpers\Bitrix\Sitemap;


use Bitrix\Main\Loader;
use Bitrix\Main\Text\Encoding;
use CCatalogSKU;
use CIBlockElement;
use CIBlockProperty;
use CIBlockPropertyEnum;
use CIBlockSectionPropertyLink;
use CTimeZone;

class FilterSitemap
{
    private static $cache = [];
    public $propsValues;
    private $IBLOCK_ID;
    private $SECTION_ID;
    private $SKU_IBLOCK_ID;
    private $SKU_PROPERTY_ID;
    private $SAFE_FILTER_NAME;
    private $arResult = [];
    private $maxValues = 0;

    public function __construct(int $iblockId, int $sectionId, $maxValues = 3)
    {
        Loader::includeModule('iblock');
        Loader::includeModule('catalog');
        $this->IBLOCK_ID = $iblockId;
        $this->SECTION_ID = $sectionId;
        $this->SAFE_FILTER_NAME = 'arrFilter';
        $this->maxValues = $maxValues;

        if (Loader::includeModule('catalog')) {
            $arCatalog = CCatalogSKU::GetInfoByProductIBlock($this->IBLOCK_ID);
            if (!empty($arCatalog)) {
                $this->SKU_IBLOCK_ID = $arCatalog["IBLOCK_ID"];
                $this->SKU_PROPERTY_ID = $arCatalog["SKU_PROPERTY_ID"];
            }
        }
    }

    public function execute()
    {
        $arResult = &$this->arResult;
        $arResult["ITEMS"] = $this->getResultItems();

        $propertyEmptyValuesCombination = array();
        foreach ($arResult["ITEMS"] as $PID => $arItem) {
            $propertyEmptyValuesCombination[$arItem["ID"]] = array();
        }

        $arElementFilter = array(
            "IBLOCK_ID" => $this->IBLOCK_ID,
            "SUBSECTION" => $this->SECTION_ID,
            "SECTION_SCOPE" => "IBLOCK",
            "ACTIVE_DATE" => "Y",
            "ACTIVE" => "Y",
            "CHECK_PERMISSIONS" => "Y",
        );

        if (!empty($this->arResult["PROPERTY_ID_LIST"])) {
            $rsElements = CIBlockElement::GetPropertyValues($this->IBLOCK_ID, $arElementFilter, false, array('ID' => $this->arResult["PROPERTY_ID_LIST"]));
            while ($arElement = $rsElements->Fetch()) {
                $arElements[$arElement["IBLOCK_ELEMENT_ID"]] = $arElement;
            }
        } else {
            $rsElements = CIBlockElement::GetList(array('ID' => 'ASC'), $arElementFilter, false, false, array('ID', 'IBLOCK_ID'));
            while ($arElement = $rsElements->Fetch()) {
                $arElements[$arElement["ID"]] = array();
            }
        }

        if (!empty($arElements) && $this->SKU_IBLOCK_ID && $arResult["SKU_PROPERTY_COUNT"] > 0) {
            $arSkuFilter = array(
                "IBLOCK_ID" => $this->SKU_IBLOCK_ID,
                "ACTIVE_DATE" => "Y",
                "ACTIVE" => "Y",
                "CHECK_PERMISSIONS" => "Y",
                "=PROPERTY_" . $this->SKU_PROPERTY_ID => array_keys($arElements),
            );
            $arSkuFilter['AVAILABLE'] = 'Y';

            $rsElements = CIBlockElement::GetPropertyValues($this->SKU_IBLOCK_ID, $arSkuFilter, false, array('ID' => $this->arResult["SKU_PROPERTY_ID_LIST"]));
            while ($arSku = $rsElements->Fetch()) {
                foreach ($arResult["ITEMS"] as $PID => $arItem) {
                    if (isset($arSku[$PID]) && $arSku[$this->SKU_PROPERTY_ID] > 0) {
                        if (is_array($arSku[$PID])) {
                            foreach ($arSku[$PID] as $value)
                                $arElements[$arSku[$this->SKU_PROPERTY_ID]][$PID][] = $value;
                        } else {
                            $arElements[$arSku[$this->SKU_PROPERTY_ID]][$PID][] = $arSku[$PID];
                        }
                    }
                }
            }
        }

        CTimeZone::Disable();
        foreach ($arElements as $arElement) {
            foreach ($arResult["ITEMS"] as $PID => $arItem) {
                if (is_array($arElement[$PID])) {
                    foreach ($arElement[$PID] as $value) {
                        $this->fillItemValues($arResult["ITEMS"][$PID], $value);
                    }
                } elseif ($arElement[$PID] !== false) {
                    $this->fillItemValues($arResult["ITEMS"][$PID], $arElement[$PID]);
                }
            }
        }
        CTimeZone::Enable();

        return $this->propsValues;
    }

    private function getResultItems()
    {
        $items = $this->getIBlockItems($this->IBLOCK_ID);
        $this->arResult["PROPERTY_COUNT"] = count($items);
        $this->arResult["PROPERTY_ID_LIST"] = array_keys($items);

        if ($this->SKU_IBLOCK_ID) {
            $this->arResult["SKU_PROPERTY_ID_LIST"] = array($this->SKU_PROPERTY_ID);
            foreach ($this->getIBlockItems($this->SKU_IBLOCK_ID) as $PID => $arItem) {
                $items[$PID] = $arItem;
                $this->arResult["SKU_PROPERTY_COUNT"]++;
                $this->arResult["SKU_PROPERTY_ID_LIST"][] = $PID;
            }
        }

        return $items;
    }

    private function getIBlockItems(int $IBLOCK_ID)
    {
        $items = array();

        foreach (CIBlockSectionPropertyLink::GetArray($IBLOCK_ID, $this->SECTION_ID) as $PID => $arLink) {
            if ($arLink["SMART_FILTER"] !== "Y")
                continue;

            if ($arLink["ACTIVE"] === "N")
                continue;

            $rsProperty = CIBlockProperty::GetByID($PID);
            $arProperty = $rsProperty->Fetch();
            if ($arProperty) {
                $items[$arProperty["ID"]] = array(
                    "CODE" => $arProperty["CODE"],
                    "PROPERTY_TYPE" => $arProperty["PROPERTY_TYPE"],
                    "USER_TYPE" => $arProperty["USER_TYPE"],
                    "USER_TYPE_SETTINGS" => $arProperty["USER_TYPE_SETTINGS"],
                );
            }
        }
        return $items;
    }

    private function fillItemValues(&$resultItem, $arProperty)
    {
        if (is_array($arProperty)) {
            if (isset($arProperty["PRICE"])) {
                return null;
            }
            $key = $arProperty["VALUE"];
            $PROPERTY_TYPE = $arProperty["PROPERTY_TYPE"];
            $PROPERTY_USER_TYPE = $arProperty["USER_TYPE"];
            $PROPERTY_ID = $arProperty["ID"];
        } else {
            $key = $arProperty;
            $PROPERTY_TYPE = $resultItem["PROPERTY_TYPE"];
            $PROPERTY_USER_TYPE = $resultItem["USER_TYPE"];
            $PROPERTY_ID = $resultItem["ID"];
            $arProperty = $resultItem;
        }

        if ($PROPERTY_TYPE == "F") {
            return null;
        } elseif ($PROPERTY_TYPE == "N") {
            return null;
        } elseif ($arProperty["DISPLAY_TYPE"] == "U") {
            return null;
        } elseif ($PROPERTY_TYPE == "E" && $key <= 0) {
            return null;
        } elseif ($PROPERTY_TYPE == "G" && $key <= 0) {
            return null;
        } elseif (strlen($key) <= 0) {
            return null;
        }

        $arUserType = array();
        if ($PROPERTY_USER_TYPE != "") {
            $arUserType = CIBlockProperty::GetUserType($PROPERTY_USER_TYPE);
            if (isset($arUserType["GetExtendedValue"]))
                $PROPERTY_TYPE = "Ux";
            elseif (isset($arUserType["GetPublicViewHTML"]))
                $PROPERTY_TYPE = "U";
        }

        if ($PROPERTY_USER_TYPE === "DateTime") {
            $key = call_user_func_array(
                $arUserType["GetPublicViewHTML"],
                array(
                    $arProperty,
                    array("VALUE" => $key),
                    array("MODE" => "SIMPLE_TEXT", "DATETIME_FORMAT" => "SHORT"),
                )
            );
            $PROPERTY_TYPE = "S";
        }

        $htmlKey = htmlspecialcharsbx($key);
        if (isset($resultItem["VALUES"][$htmlKey])) {
            return $htmlKey;
        }

        $file_id = null;
        $url_id = null;

        switch ($PROPERTY_TYPE) {
            case "L":
                $enum = CIBlockPropertyEnum::GetByID($key);
                if ($enum) {
                    if (function_exists('get_translit')) {
                        $url_id = get_translit($enum["VALUE"]);
                    } else {
                        $url_id = toLower($enum["XML_ID"]);
                    }
                } else {
                    return null;
                }
                break;
            case "Ux":
                if (!isset(self::$cache[$PROPERTY_ID]))
                    self::$cache[$PROPERTY_ID] = array();

                if (!isset(self::$cache[$PROPERTY_ID][$key])) {
                    self::$cache[$PROPERTY_ID][$key] = call_user_func_array(
                        $arUserType["GetExtendedValue"],
                        array(
                            $arProperty,
                            array("VALUE" => $key),
                        )
                    );
                }

                if (self::$cache[$PROPERTY_ID][$key]) {
                    $value = self::$cache[$PROPERTY_ID][$key]['VALUE'];
                    if (function_exists('get_translit')) {
                        $url_id = get_translit($value);
                    } else {
                        $url_id = toLower(self::$cache[$PROPERTY_ID][$key]['UF_XML_ID']);
                    }
                } else {
                    return null;
                }
                break;
        }

        if (strlen($url_id)) {
            $error = "";
            $utf_id = Encoding::convertEncoding($url_id, LANG_CHARSET, "utf-8", $error);
            $resultItem["VALUES"][$htmlKey]['URL_ID'] = rawurlencode(str_replace("/", "-", $utf_id));
        }

        if (count($this->propsValues[strtolower($resultItem['CODE'])]) < $this->maxValues && $url_id) {
            $this->propsValues[strtolower($resultItem['CODE'])][] = $url_id;
        }

        return $htmlKey;
    }

    public function makeSmartUrl($url, array $smartParts)
    {
        return str_replace("#SMART_FILTER_PATH#", implode("/", $this->encodeSmartParts($smartParts)), $url);
    }

    public function encodeSmartParts($smartParts)
    {
        foreach ($smartParts as &$smartPart) {
            $urlPart = "";
            foreach ($smartPart as $i => $smartElement) {
                if (!$urlPart)
                    $urlPart .= $smartElement;
                elseif ($i == 'from' || $i == 'to')
                    $urlPart .= '-' . $i . '-' . $smartElement;
                elseif ($i == 1)
                    $urlPart .= '-is-' . $smartElement;
                else
                    $urlPart .= '-or-' . $smartElement;
            }
            $smartPart = $urlPart;
        }
        unset($smartPart);
        return $smartParts;
    }

    public function makeCombinations(array $result)
    {
        $smartParts = [];
        $excludedKeys = $excludedKeys2 = [];
        if (!defined('URL_PROPERTIES_COUNT')) {
            define('URL_PROPERTIES_COUNT', 1);
        }

        if (URL_PROPERTIES_COUNT >= 1) {
            foreach ($result as $key => $values) {
                $excludedKeys[] = $key;
                foreach ($values as $value) {
                    $smartPart = [$key, $value];
                    $smartParts[] = [$smartPart];
                    if (URL_PROPERTIES_COUNT >= 2) {
                        foreach ($result as $key2 => $values2) {
                            if (in_array($key2, $excludedKeys)) {
                                continue;
                            }
                            $excludedKeys2 = array_merge($excludedKeys, $excludedKeys2, [$key2]);
                            foreach ($values2 as $value2) {
                                $smartPart2 = [$key2, $value2];
                                $smartParts[] = [
                                    $smartPart,
                                    $smartPart2,
                                ];
                                if (URL_PROPERTIES_COUNT >= 3) {
                                    foreach ($result as $key3 => $values3) {
                                        if (in_array($key3, $excludedKeys2)) {
                                            continue;
                                        }
                                        foreach ($values3 as $value3) {
                                            $smartPart3 = [$key3, $value3];
                                            $smartParts[] = [
                                                $smartPart,
                                                $smartPart3,
                                            ];
                                            $smartParts[] = [
                                                $smartPart,
                                                $smartPart2,
                                                $smartPart3,
                                            ];
                                        }
                                    }
                                }
                            }
                        }
                        $excludedKeys2 = [];
                    }
                }
            }
        }

        return $smartParts;
    }
}