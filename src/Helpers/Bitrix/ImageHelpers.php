<?php


namespace Palladiumlab\Helpers\Bitrix;


class ImageHelpers
{
    protected const RESIZE_IMAGE_SIZE = [
        'width' => 530,
        'height' => 760,
    ];
    protected const CACHE_TIME = 60 * 60 * 24 * 30 * 3;

    public static function getResizePath(int $imageId, int $width = 0, int $height = 0, int $resizeType = BX_RESIZE_IMAGE_EXACT)
    {
        $result = '';
        $size = ($width > 0 && $height > 0) ? [
            'width' => $width,
            'height' => $height,
        ] : self::RESIZE_IMAGE_SIZE;

        $cache = bitrix_app()->getCache();
        $cacheKey = md5(__FUNCTION__) . "_{$imageId}";
        if ($cache->initCache(self::CACHE_TIME, $cacheKey, "images/resize")) {
            $result = $cache->getVars();
        } else if ($cache->startDataCache(self::CACHE_TIME, $cacheKey, "images/resize")) {
            if ($imageId > 0) {
                $result = \CFile::ResizeImageGet(
                    $imageId,
                    $size,
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