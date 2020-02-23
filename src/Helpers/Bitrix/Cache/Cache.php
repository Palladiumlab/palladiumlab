<?php


namespace Palladiumlab\Helpers\Bitrix\Cache;


use Exception;

/**
 * Class Cache
 * @package Palladiumlab\Helpers
 */
class Cache
{
    /**
     * @param Config $config
     * @param callable $callback
     * @return mixed
     */
    public static function start(Config $config, callable $callback)
    {
        $result = false;

        try {
            $cache = bitrix_app()->getCache();
            $taggedCache = bitrix_app()->getTaggedCache();
            if ($cache->initCache($config->getTime(), $config->getKey(), $config->getPath())) {
                $result = $cache->getVars();
            } elseif ($cache->startDataCache($config->getTime(), $config->getKey(), $config->getPath())) {
                $taggedCache->startTagCache($config->getPath());
                $result = $callback($taggedCache, $config);
                if (!$result) {
                    $taggedCache->abortTagCache();
                    $cache->abortDataCache();
                } else {
                    $taggedCache->endTagCache();
                    $cache->endDataCache($result);
                }
            }
        } catch (Exception $e) {
            return $result;
        }

        return $result;
    }
}
