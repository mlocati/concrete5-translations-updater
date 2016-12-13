<?php

namespace MLocati\TranslationsUpdater\LanguageCollector;

use Concrete\Core\Support\Facade\Application;
use Exception;
use MLocati\TranslationsUpdater\ResourceStats;

abstract class LanguageCollector
{
    /**
     * Cache key.
     *
     * @var string
     */
    const CACHE_KEY = 'translations_updater.resourceStats';

    /**
     * Cache lifetime (in seconds).
     *
     * @var int
     */
    const CACHE_LIFETIME = 82800; // 23 hours

    /**
     * Get the URL to the stats file.
     *
     * @return string
     */
    abstract protected function getInfoURL();

    /**
     * @param array $data
     *
     * @return ResourceStats[]
     */
    abstract protected function parseInfoData($data);

    /**
     * @return static[]
     */
    protected static function getAllCollectors()
    {
        $result = array();
        $hDir = @opendir(__DIR__);
        if ($hDir) {
            $me = basename(__FILE__);
            for (; $item = @readdir($hDir); $item !== false) {
                if (strcasecmp($item, $me) !== 0 && preg_match('/^(\w.*)\.php$/i', $item, $m)) {
                    $fqn = __NAMESPACE__.'\\'.$m[1];
                    $result[] = new $fqn();
                }
            }
            @closedir($hDir);
        }

        return $result;
    }

    /**
     * @param bool $forceRefresh
     *
     * @return ResourceStats[]
     */
    public static function getResourceStats($forceRefresh = false)
    {
        $app = Application::getFacadeApplication();
        $cache = $app->make('cache/expensive');
        $cacheItem = $cache->getItem(static::CACHE_KEY);
        $result = null;
        if (!$forceRefresh && !$cacheItem->isMiss()) {
            try {
                $result = $cacheItem->get();
                if (empty($result) | !is_array($result) || !($result[0] instanceof ResourceStats)) {
                    $result = null;
                }
            } catch (Exception $x) {
                $result = null;
            }
        }
        if ($result === null) {
            $result = array();
            $client = $app->make('http/client');
            foreach (static::getAllCollectors() as $collector) {
                $client->setUri($collector->getInfoURL());
                $rawData = $client->send()->getBody();
                $result = array_merge($result, $collector->parseInfoData($rawData));
            }
            if (method_exists($cacheItem, 'expiresAfter')) {
                $cacheItem->expiresAfter(static::CACHE_LIFETIME);
                $cacheItem->set($result);
                $cacheItem->save();
            } else {
                $cacheItem->set($result, static::CACHE_LIFETIME);
            }
        }

        return $result;
    }
}
