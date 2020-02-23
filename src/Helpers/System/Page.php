<?php


namespace Palladiumlab\Helpers;


use Palladiumlab\Templates\Singleton;

class Page extends Singleton
{
    /**
     * @return Page
     */
    public static function getInstance(): Singleton
    {
        return parent::getInstance();
    }

    public static function isPage($pages, bool $strict = false): bool
    {
        $curPage = self::getCurrentPage();
        $pages = array_wrap($pages);
        foreach ($pages as $page) {
            if (($strict && $curPage === (string)$page)
                || (starts_with($curPage, (string)$page) && !$strict)) {
                return true;
            }
        }

        return false;
    }

    public static function getCurrentPage($withIndex = false): string
    {
        return (string)bitrix_global_app()->GetCurPage((bool)$withIndex);
    }
}