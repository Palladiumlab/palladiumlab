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
}
