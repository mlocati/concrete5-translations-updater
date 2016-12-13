<?php
namespace MLocati\TranslationsUpdater\LanguageCollector;

use SimpleXMLElement;
use MLocati\TranslationsUpdater\ResourceStats;

class CoreLanguageCollector extends LanguageCollector
{
    /**
     * {@inheritdoc}
     *
     * @see LanguageCollector::getInfoURL()
     */
    protected function getInfoURL()
    {
        return 'https://github.com/concrete5/concrete5-translations/raw/master/stats-current.xml';
    }

    /**
     * {@inheritdoc}
     *
     * @see LanguageCollector::parseInfoData()
     */
    protected function parseInfoData($data)
    {
        $result = [];
        $xml = new SimpleXMLElement($data);
        $resources = $xml->xpath('/stats/resource');
        if (is_array($resources)) {
            foreach ($resources as $resource) {
                $stats = new ResourceStats();
                $stats->setVersion($resource['name']);
                $languages = $resource->xpath('./language');
                if (is_array($languages)) {
                    foreach ($languages as $language) {
                        $stats->setLocaleProgress($language['name'], $language['percentual']);
                    }
                }
                if (count($stats->getLocaleIDs()) > 0) {
                    $result[] = $stats;
                }
            }
        }

        return $result;
    }
}
