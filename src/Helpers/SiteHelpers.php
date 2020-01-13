<?php


namespace Palladiumlab\Helpers;


class SiteHelpers
{
    public static function getPhoneForLink($includePath = 'include/config/phone_tag.php')
    {
        ob_start();
        global $APPLICATION;
        $APPLICATION->IncludeComponent("bitrix:main.include", "", [
            "AREA_FILE_SHOW" => "file",
            "PATH" => SITE_DIR . $includePath
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

        return starts_with($phone, '8') ? $phone : "+{$phone}";
    }
}