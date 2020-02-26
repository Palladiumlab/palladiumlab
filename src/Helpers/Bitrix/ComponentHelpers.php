<?php


namespace Palladiumlab\Helpers\Bitrix;


class ComponentHelpers
{
    public static function fixFileProps(&$result, array $availablePropCodes)
    {
        // Bitrix, damn you bitrix/modules/iblock/lib/component/element.php:1539
        foreach ($result['PROPERTIES'] as $propCode => $property) {
            $displayProps = &$result['DISPLAY_PROPERTIES'];
            if ($property['PROPERTY_TYPE'] === 'F' && in_array($propCode, $availablePropCodes) && !empty($property['VALUE'])) {
                $displayProps[$propCode] = \CIBlockFormatProperties::GetDisplayValue($result, $property, 'catalog_out');
                if (!empty($displayProps[$propCode]['FILE_VALUE']['SRC'])) {
                    $displayProps[$propCode]['FILE_VALUE'] = [$displayProps[$propCode]['FILE_VALUE']];
                }
            }
        }
    }

    public static function menuConversion(array $menuItems)
    {
        if (!empty($menuItems)) {
            $formattedResult = [];
            $parents = [];
            foreach ($menuItems as $index => $item) {
                if (empty($parents) || $item['DEPTH_LEVEL'] === 1) {
                    $parent = &$formattedResult;
                } else {
                    $parent = &$parents[$item['DEPTH_LEVEL'] - 1];
                }
                $parent['CHILDREN'][$index] = $item;
                if ($item['IS_PARENT']) {
                    $parents[$item['DEPTH_LEVEL']] = &$parent['CHILDREN'][$index];
                }
            }
            return $formattedResult['CHILDREN'];
        }
        return $menuItems;
    }
}
