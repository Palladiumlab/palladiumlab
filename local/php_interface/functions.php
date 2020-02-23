<?php

use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\Page\Asset;
use Palladiumlab\Helpers\Bitrix\ImageHelpers;
use Palladiumlab\Helpers\Site;
use Palladiumlab\Helpers\System\Debug;

if (!function_exists('restart_buffer')) {
    function restart_buffer()
    {
        global $APPLICATION;
        $APPLICATION->RestartBuffer();
        $APPLICATION->RestartWorkarea();
    }
}

if (!function_exists('ddr')) {
    function ddr(...$vars)
    {
        restart_buffer();
        dd(...$vars);
    }
}

if (!function_exists('bitrix_asset')) {
    function bitrix_asset()
    {
        return Asset::getInstance();
    }
}

if (!function_exists('bitrix_app')) {
    function bitrix_app()
    {
        return Application::getInstance();
    }
}

if (!function_exists('bitrix_global_app')) {
    function bitrix_global_app()
    {
        global $APPLICATION;
        return $APPLICATION;
    }
}

if (!function_exists('bitrix_context')) {
    function bitrix_context()
    {
        return Context::getCurrent();
    }
}

if (!function_exists('bitrix_server')) {
    function bitrix_server()
    {
        return bitrix_context()->getServer();
    }
}

if (!function_exists('bitrix_request')) {
    function bitrix_request()
    {
        return bitrix_context()->getRequest();
    }
}

if (!function_exists('host_url')) {
    function host_url($withoutLastSlash = false)
    {
        $lastSlash = $withoutLastSlash ? '' : '/';
        return get_protocol() . bitrix_server()->getServerName() . $lastSlash;
    }
}

if (!function_exists('get_protocol')) {
    function get_protocol()
    {
        return bitrix_request()->isHttps() ? "https://" : "http://";
    }
}

if (!function_exists('template_path')) {
    function template_path()
    {
        return defined('SITE_TEMPLATE_PATH') ? SITE_TEMPLATE_PATH : '/';
    }
}

if (!function_exists('get_canonical')) {
    function get_canonical($isAbsolute = true)
    {
        global $APPLICATION;
        if ($isAbsolute) {
            return host_url(true) . $APPLICATION->GetCurPage(false);
        }
        return $APPLICATION->GetCurPage(false);
    }
}

if (!function_exists('price_formatted')) {
    function price_formatted($price, $thousandsSep = ' ')
    {
        $price = Site::getSafetyString($price);
        // Replace all not-digits with empty string
        $price = preg_replace('/\D+/', '', $price);

        return number_format((float)$price, 0, ',', $thousandsSep);
    }
}

if (!function_exists('plural_mess')) {
    function plural_mess($number, array $after)
    {
        /* @var $after array варианты написания для количества 1, 2 и 5 */
        $cases = array(2, 0, 1, 1, 1, 2);
        return $after[($number % 100 > 4 && $number % 100 < 20) ? 2 : $cases[min($number % 10, 5)]];
    }
}

if (!function_exists('plural_form')) {
    function plural_form($number, array $after)
    {
        /* @var $after array варианты написания для количества 1, 2 и 5 */
        $cases = array(2, 0, 1, 1, 1, 2);
        return $number . ' ' . $after[($number % 100 > 4 && $number % 100 < 20) ? 2 : $cases[min($number % 10, 5)]];
    }
}

if (!function_exists('formatted_date')) {
    function formatted_date($date, $format = 'd F Y')
    {
        return mb_strtolower(FormatDate($format, MakeTimeStamp($date)));
    }
}

if (!function_exists('str_replace_once')) {
    function str_replace_once($search, $replace, $text)
    {
        $pos = strpos($text, $search);
        return $pos !== false ? substr_replace($text, $replace, $pos, strlen($search)) : $text;
    }
}

if (!function_exists('is_cli')) {
    function is_cli()
    {
        return php_sapi_name() === 'cli';
    }
}

if (!function_exists('mb_ucfirst')) {
    function mb_ucfirst($string, $enc = 'UTF-8')
    {
        return mb_strtoupper(mb_substr($string, 0, 1, $enc), $enc) .
            mb_substr($string, 1, mb_strlen($string, $enc), $enc);
    }
}

if (!function_exists('get_image_resize')) {
    function get_image_resize(int $imageId)
    {
        return ImageHelpers::getResizePath($imageId);
    }
}

if (!function_exists('get_transliterate')) {
    function get_transliterate(string $value, string $lang = '')
    {
        return trim(Site::transliterate(toLower($value), !empty($lang) ? $lang : LANGUAGE_ID, [
            "change_case" => 'L', // 'L' - toLower, 'U' - toUpper, false - do not change
            "replace_space" => '-',
            "replace_other" => '-',
            "delete_repeat_replace" => true,
        ]), ' -');
    }
}

if (!function_exists('debugLogger')) {
    function debugLogger()
    {
        return Debug::getLogger();
    }
}
