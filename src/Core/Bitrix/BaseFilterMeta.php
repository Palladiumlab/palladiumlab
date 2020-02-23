<?php


namespace Palladiumlab\Core\Bitrix;

use Bitrix\Iblock\Model\Section;
use Bitrix\Main\ORM\Data\DataManager;

/**
 * Class Meta
 * Seo meta for filters
 * @package Palladiumlab\Core\Bitrix
 */
class BaseFilterMeta
{
    protected const H1_TEMPLATE = 'UF_FILTER_H1';
    protected const TITLE_TEMPLATE = 'UF_FILTER_SEO_TITLE';
    protected const DESC_TEMPLATE = 'UF_FILTER_DESC';
    protected const PRICES_PLURAL = [
        'рубль',
        'рубля',
        'рублей',
    ];
    protected const IBLOCK_CATALOG_ID = 0;

    public static function setMetaOnEpilog()
    {
        $currentSectionId = (int)bitrix_global_app()->GetPageProperty('section_id');
        if ($currentSectionId > 0) {
            static::setMeta(static::getSectionMetaTemplates($currentSectionId));
        }
    }

    protected static function setMeta(array $metaTemplates)
    {
        if (!empty($metaTemplates)) {
            foreach ($metaTemplates as $code => $metaTemplate) {
                $filterString = bitrix_global_app()->GetPageProperty('filter_string', '');
                if (!empty($metaTemplate) && !empty($filterString)) {
                    $metaTemplate = str_replace('#filter#', $filterString, $metaTemplate);
                    switch ($code) {
                        case static::H1_TEMPLATE:
                            bitrix_global_app()->SetTitle($metaTemplate);
                            break;
                        case static::TITLE_TEMPLATE:
                            bitrix_global_app()->SetPageProperty('title', $metaTemplate);
                            break;
                        case static::DESC_TEMPLATE:
                            bitrix_global_app()->SetPageProperty('description', $metaTemplate);
                            break;
                    }
                }
            }
        }
    }

    private static function getSectionMetaTemplates(int $sectionId)
    {
        /** @var DataManager $entity */
        $entity = Section::compileEntityByIblock(static::IBLOCK_CATALOG_ID);
        return $entity::getRow([
            'select' => [static::H1_TEMPLATE, static::DESC_TEMPLATE, static::TITLE_TEMPLATE],
            'filter' => ['=ID' => $sectionId]
        ]) ?: [];
    }

    public static function generateMetaFilter(array $filterItems)
    {
        global $APPLICATION;
        $result = '';

        foreach ($filterItems as $filterItem) {
            $prefix = empty($result) ? '' : ' ';
            $postfix = ',';
            if (!empty($filterItem['VALUES'])) {
                if ($filterItem['PRICE']) {
                    $result .= self::generatePriceMeta($filterItem, $postfix, $prefix);
                } else {
                    $result .= self::generatePropMeta($filterItem['CODE'], $filterItem, $postfix, $prefix);
                }
            }
        }
        $result = trim($result, ',');

        if (!empty($result)) {
            $APPLICATION->SetPageProperty('filter_string', $result);
        }
    }

    protected static function generatePriceMeta(array $filterItem, string $postfix, string $prefix)
    {
        $result = '';
        if (!empty($filterItem['VALUES']['MIN'])) {
            $result .= $prefix . 'больше ' . price_formatted($filterItem['VALUES']['MIN'])
                . ' ' . plural_mess($filterItem['VALUES']['MIN'], self::PRICES_PLURAL) . $postfix;
        } else if (!empty($filterItem['VALUES']['MAX'])) {
            $result .= $prefix . 'до ' . price_formatted($filterItem['VALUES']['MAX'])
                . ' ' . plural_mess($filterItem['VALUES']['MIN'], self::PRICES_PLURAL) . $postfix;
        } else if (!empty($filterItem['VALUES']['MIN']) && !empty($filterItem['VALUES']['MAX'])) {
            $result .= $prefix . 'от ' . price_formatted($filterItem['VALUES']['MIN'])
                . ' до' . price_formatted($filterItem['VALUES']['MAX'])
                . ' ' . plural_mess($filterItem['VALUES']['MAX'], self::PRICES_PLURAL) . $postfix;
        }

        return $result;
    }

    protected static function generatePropMeta(string $propCode, array $filterItem, string $postfix, string $prefix)
    {
        $result = '';
        switch ($propCode) {
            default:
                $result .= $prefix . implode(', ', $filterItem['VALUES']) . $postfix;
                break;
        }

        return $result;
    }

    public static function generateCurrentValuesForFilter(array $items)
    {
        $currentValues = [];
        foreach ($items as $key => $item) {
            if ($item['PRICE']) {
                foreach (['MAX', 'MIN'] as $priceItem) {
                    if (!empty($item['VALUES'][$priceItem]['HTML_VALUE'])) {
                        $currentValues[$key]['VALUES'][$priceItem] = $item['VALUES'][$priceItem]['HTML_VALUE'];
                    }
                }
            } else {
                $values = array_filter($item['VALUES'], function ($value) {
                    return $value['CHECKED'];
                });
                foreach ($values as $valueId => $value) {
                    $currentValues[$key]['VALUES'][$valueId] = $value['VALUE'];
                }
            }
            if (!empty($currentValues[$key]['VALUES']) && empty($currentValues[$key]['ID'])) {
                $currentValues[$key] = array_merge($currentValues[$key], [
                    'ID' => $item['ID'],
                    'PRICE' => !!$item['PRICE'],
                    'CODE' => $item['CODE'],
                    'NAME' => $item['NAME'],
                ]);
            }
        }
        return $currentValues;
    }
}