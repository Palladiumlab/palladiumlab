<?php


namespace Palladiumlab\Helpers;


use Bitrix\Main\Config\Option;
use Bitrix\Main\Mail\Event;

/**
 * Class Mail
 * @package Palladiumlab\Helpers
 */
class Mail
{
    protected const LANGUAGE_ID = 'ru';
    protected const SITE_ID = 's1';

    public static function send(string $event, array $fields)
    {
        return Event::send([
            'EVENT_NAME' => $event,
            'LID' => $fields['LID'] ?: self::SITE_ID,
            'DUPLICATE' => 'N',
            'LANGUAGE_ID' => $fields['LANGUAGE_ID'] ?: self::LANGUAGE_ID,
            'C_FIELDS' => array_merge($fields, [
                'DEFAULT_EMAIL_FROM' => Option::get('main', 'email_from', '')
            ]),
        ]);
    }
}