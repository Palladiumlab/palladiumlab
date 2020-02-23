<?php


namespace Palladiumlab\Helpers;


use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\IO\File;
use Illuminate\Support\Arr;
use Palladiumlab\Helpers\System\Lang;

class Site
{
    public static function getPhoneForLink(string $includePath = '/include/phone.php')
    {
        $phone = self::getSafetyString(self::getIncludeContent($includePath));
        // Replace all not-digits with empty string
        $phone = preg_replace('/\D+/', '', $phone);
        if (empty($phone)) {
            return $phone;
        }
        return starts_with($phone, '8') ? $phone : "+{$phone}";
    }

    public static function getSafetyString(string $string)
    {
        // Strip HTML Tags
        $string = strip_tags($string);
        // Clean up things like &amp;
        $string = html_entity_decode($string);
        // Strip out any url-encoded stuff
        $string = urldecode($string);
        // Replace Multiple spaces with empty string
        $string = preg_replace('/ +/', '', $string);
        // Trim the string of leading/trailing space
        $string = trim($string);

        return $string;
    }

    public static function getIncludeContent(string $includePath)
    {
        $fileExist = (File::isFileExists($includePath)
            || File::isFileExists(bitrix_server()->getDocumentRoot() . $includePath));
        if (empty($includePath) || $fileExist) {
            return '';
        }
        ob_start();
        global $APPLICATION;
        $APPLICATION->IncludeComponent('bitrix:main.include', '', [
            'AREA_FILE_SHOW' => 'file',
            'PATH' => $includePath,
        ]);
        return ob_get_clean();
    }

    public static function getEmailForLink(string $includePath = '/include/email.php')
    {
        return self::getSafetyString(self::getIncludeContent($includePath));
    }

    public static function transliterate(string $str, string $lang, array $params = array())
    {
        return Lang::transliterate($str, $lang, $params);
    }

    public static function getFormattedDate($date, $format = 'd M Y')
    {
        return mb_strtolower(FormatDate($format, MakeTimeStamp($date)));
    }

    public static function getAdminLink(int $iblockId, int $elementId, string $iblockType, array $fields)
    {
        $query = Arr::query(array_merge([
            'lang' => 'ru',
            'IBLOCK_ID' => $iblockId,
            'type' => $iblockType,
            'ID' => $elementId,
        ], $fields));
        return UrlManager::getInstance()->getHostUrl() . "/bitrix/admin/iblock_element_edit.php?{$query}";
    }
}