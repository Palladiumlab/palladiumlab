<?php


namespace Palladiumlab\Helpers;


class ComponentHelpers
{
    public static function fixFileProps(&$result, array $availablePropCodes)
    {
        // Bitrix, fuck you bitrix/modules/iblock/lib/component/element.php:1539
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

    public static function filterPrices(float $priceFrom, float $priceTo)
    {
        $result = [];
        list($fromValue, $toValue) = [
            $priceFrom > 1000 ? round($priceFrom / 1000) * 1000 : $priceFrom,
            $priceTo > 1000 ? round($priceTo / 1000) * 1000 : $priceTo,
        ];

        $step = false;
        $maxSteps = 5;
        if ($toValue >= 150000) {
            $step = 20;
        } else if ($toValue >= 150000 && $toValue <= 100000) {
            $step = 10;
        } else if ($toValue >= 20000 && $toValue <= 50000) {
            $step = 5;
        } else if ($toValue >= 10000 && $toValue <= 20000) {
            $step = 5;
        } else if ($toValue >= 1000 && $toValue <= 10000) {
            $step = 1;
        }

        $result[] = [
            'from' => (string)$fromValue,
            'to' => '',
        ];
        $firstStep = $step;
        while ($step) {
            $result[] = [
                'from' => (string)($step * 1000),
                'to' => (string)(($step + $step) * 1000),
            ];
            if ($step >= $firstStep * $maxSteps) {
                $step = false;
            } else {
                $step += $step;
            }
        }
        $result[] = [
            'from' => '',
            'to' => (string)$toValue,
        ];
        ddr([
            $firstStep,
            $result
        ]);
    }
}