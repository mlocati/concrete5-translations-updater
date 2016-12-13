<?php

namespace MLocati\TranslationsUpdater\LanguageCollector;

use MLocati\TranslationsUpdater\ResourceStats;

class PackagesLanguageCollector extends LanguageCollector
{
    /**
     * {@inheritdoc}
     *
     * @see LanguageCollector::getInfoURL()
     */
    protected function getInfoURL()
    {
        return 'https://github.com/concrete5/package-translations/raw/gh-pages/js/data.js';
    }

    /**
     * {@inheritdoc}
     *
     * @see LanguageCollector::parseInfoData()
     */
    protected function parseInfoData($data)
    {
        $result = array();
        $obj = @json_decode($data, true);
        if (is_array($obj) && isset($obj['packages']) && is_array($obj['packages'])) {
            foreach ($obj['packages'] as $package) {
                $stats = new ResourceStats();
                $stats->setHandle($package['handle'])->setName($package['name']);
                if (isset($package['locales']) && is_array($package['locales'])) {
                    foreach ($package['locales'] as $localeID => $localeData) {
                        $stats->setLocaleProgress($localeID, $localeData['perc']);
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
