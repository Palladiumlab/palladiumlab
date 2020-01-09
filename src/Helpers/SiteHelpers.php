<?php


namespace Palladiumlab\Helpers;


class SiteHelpers
{
    public static function getDressCount()
    {
        return $count = 10800 + (round((NOW - strtotime('2017-10-07')) / 86400) * 3);
    }

    public static function setCookie($key, $val = false, $time = 31536000)
    {
        return setcookie($key, $val, NOW + $time, '/', server()->getServerName(), get_protocol());
    }

    public static function getPhoneForLink()
    {
        ob_start();
        global $APPLICATION;
        $APPLICATION->IncludeComponent("bitrix:main.include", "", [
            "AREA_FILE_SHOW" => "file",
            "PATH" => SITE_DIR . "include/config/phone_tag.php"
        ]);
        $phone = ob_get_clean();
        // Strip HTML Tags
        $phone = strip_tags($phone);
        // Clean up things like &amp;
        $phone = html_entity_decode($phone);
        // Strip out any url-encoded stuff
        $phone = urldecode($phone);
        // Replace all not-digits with empty string
        $phone = preg_replace('/\D+/', '', $phone);
        // Replace Multiple spaces with empty string
        $phone = preg_replace('/ +/', '', $phone);
        // Trim the string of leading/trailing space
        $phone = trim($phone);

        return "+{$phone}";
    }
}