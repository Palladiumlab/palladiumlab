<?php


namespace Palladiumlab\Helpers;


use Bitrix\Main\Application;
use CFile;

class ImageHelpers
{
    const RESIZE_IMAGE_SIZE = [
        'width' => 530,
        'height' => 760,
    ];
    const CACHE_TIME = 60 * 60;

    public static function getResizePath(int $imageId)
    {
        $result = '';

        $cache = Application::getInstance()->getCache();
        $cacheKey = md5(__FUNCTION__) . "_{$imageId}";
        if ($cache->initCache(self::CACHE_TIME, $cacheKey)) {
            $result = $cache->getVars();
        } else if ($cache->startDataCache(self::CACHE_TIME, $cacheKey)) {
            if ($imageId > 0) {
                $result = CFile::ResizeImageGet(
                    $imageId,
                    self::RESIZE_IMAGE_SIZE,
                    BX_RESIZE_IMAGE_EXACT
                )['src'];
                $cache->endDataCache($result);
            } else {
                $cache->abortDataCache();
            }
        }

        return $result;
    }
}