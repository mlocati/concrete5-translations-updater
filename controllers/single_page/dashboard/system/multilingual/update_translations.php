<?php

namespace Concrete\Package\TranslationsUpdater\Controller\SinglePage\Dashboard\System\Multilingual;

use Concrete\Core\Http\Response;
use Concrete\Core\Localization\Localization;
use Concrete\Core\Multilingual\Page\Section\Section;
use Concrete\Core\Package\BrokenPackage;
use Concrete\Core\Package\Package;
use Concrete\Core\Support\Facade\Application;
use Config;
use Gettext\Translations;
use MLocati\TranslationsUpdater\LanguageCollector\LanguageCollector;
use MLocati\TranslationsUpdater\ResourceStats;
use Punic\Comparer;
use Punic\Language;
use Symfony\Component\HttpFoundation\JsonResponse;

class UpdateTranslations extends \Concrete\Core\Page\Controller\DashboardPageController
{
    public function view()
    {
        $availablePackages = array();
        $app = isset($this->app) ? $this->app : Application::getFacadeApplication();
        if (class_exists('Concrete\Core\Package\PackageService', true)) {
            $packageService = $app->make('Concrete\Core\Package\PackageService');
            $packages = $packageService->getAvailablePackages(false);
        } else {
            $packages = Package::getAvailablePackages(false);
        }
        foreach ($packages as $package) {
            if (!($package instanceof BrokenPackage)) {
                $availablePackages[] = $package;
            }
        }
        /* @var \Concrete\Core\Package\Package[] $availablePackages */
        $currentCoreStats = null;
        $otherCoresStats = array();
        $allPackagesStats = array();
        $installedPackagesStats = array();
        $otherPackagesStats = array();
        $allStats = LanguageCollector::getResourceStats();
        usort($allStats, function (ResourceStats $a, ResourceStats $b) {
            if ($a->getHandle() === '') {
                if ($b->getHandle() === '') {
                    return version_compare($b->getDisplayVersion(), $a->getDisplayVersion());
                } else {
                    $rc = -1;
                }
            } elseif ($b->getHandle() === '') {
                $rc = 1;
            } else {
                $rc = strcasecmp($a->getName(), $b->getName());
            }

            return $rc;
        });
        $currentCoreVersion = $app->make('config')->get('concrete.version');
        $allLocales = array();
        foreach ($allStats as $stat) {
            if ($stat->getHandle() === '') {
                if ($currentCoreStats === null && version_compare($currentCoreVersion, $stat->getDisplayVersion()) >= 0) {
                    $currentCoreStats = $stat;
                } else {
                    $otherCoresStats[] = $stat;
                }
            } else {
                $installed = false;
                foreach ($availablePackages as $package) {
                    if (str_replace('-', '_', $package->getPackageHandle()) === str_replace('-', '_', $stat->getHandle())) {
                        $installed = true;
                        break;
                    }
                }
                if ($installed) {
                    $installedPackagesStats[] = $stat;
                } else {
                    $otherPackagesStats[] = $stat;
                }
            }
            foreach ($stat->getLocaleIDs() as $localeID) {
                if (!isset($allLocales[$localeID])) {
                    $allLocales[$localeID] = Language::getName($localeID);
                }
            }
        }
        $usedLocalesIDs = array();
        if (class_exists('Concrete\Core\Entity\Site\Locale', true)) {
            $em = $app->make('Doctrine\ORM\EntityManagerInterface');
            $siteLocales = $em->getRepository('Concrete\Core\Entity\Site\Locale')->findAll();
            foreach ($siteLocales as $siteLocale) {
                $usedLocalesIDs[] = $siteLocale->getLocale();
            }
        } else {
            foreach (Section::getList() as $section) {
                $usedLocalesIDs[] = $section->getLocale();
            }
        }
        $usedLocales = array();
        foreach ($usedLocalesIDs as $usedLocaleID) {
            $usedLocales[$usedLocaleID] = Language::getName($usedLocaleID);
        }
        if (!isset($usedLocales[Localization::activeLocale()])) {
            $usedLocales[Localization::activeLocale()] = Language::getName(Localization::activeLocale());
        }
        if (!isset($allLocales['en_US'])) {
            unset($usedLocales['en_US']);
        }
        $comparer = new Comparer();
        $comparer->sort($allLocales, true);
        $comparer->sort($usedLocales, true);
        $this->set('coreRelativePath', $this->getCorePath('<locale>'));
        $this->set('packageRelativePath', $this->getPackagePath('<package>', '<locale>'));
        $this->set('allLocales', $allLocales);
        $this->set('usedLocales', $usedLocales);
        $this->set('allStats', $allStats);
        $this->set('currentCoreStats', $currentCoreStats);
        $this->set('otherCoresStats', $otherCoresStats);
        $this->set('installedPackagesStats', $installedPackagesStats);
        $this->set('otherPackagesStats', $otherPackagesStats);
    }

    protected function getCorePath($localeID, $absolute = false, $extension = 'mo')
    {
        $result = '/';
        $result .= trim(str_replace(DIRECTORY_SEPARATOR, '/', REL_DIR_APPLICATION.'/'.DIRNAME_LANGUAGES), '/');
        $result .= '/'.$localeID.'/LC_MESSAGES/messages.'.$extension;

        if ($absolute) {
            $result = rtrim(str_replace('/', DIRECTORY_SEPARATOR, DIR_BASE), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, ltrim($result, '/'));
        }

        return $result;
    }

    protected function getPackagePath($packageHandle, $localeID, $absolute = false, $extension = 'mo')
    {
        $app = isset($this->app) ? $this->app : Application::getFacadeApplication();
        $packageHandle = str_replace('-', '_', $packageHandle);
        $result = '/';
        $result .= trim(str_replace(DIRECTORY_SEPARATOR, '/', $app->make('app_relative_path').'/'.DIRNAME_PACKAGES.'/'.$packageHandle.'/'.DIRNAME_LANGUAGES), '/');
        $result .= '/'.$localeID.'/LC_MESSAGES/messages.'.$extension;

        if ($absolute) {
            $result = rtrim(str_replace('/', DIRECTORY_SEPARATOR, DIR_BASE), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, ltrim($result, '/'));
        }

        return $result;
    }

    protected function getRequestedData($format = null)
    {
        $result = null;
        if ($format === null) {
            $format = $this->request->post('format');
        }
        if (in_array($format, array('po', 'mo'))) {
            $locale = $this->request->post('locale');
            if (is_string($locale) && $locale !== '') {
                $statsKey = $this->request->post('stats');
                if (is_string($statsKey) && strpos($statsKey, '@') !== false) {
                    $statsChunks = explode('@', $statsKey);
                    if (count($statsChunks) === 2) {
                        list($handle, $version) = $statsChunks;
                        $stats = null;
                        foreach (LanguageCollector::getResourceStats() as $s) {
                            if ($s->getHandle() === $handle && $s->getVersion() === $version) {
                                $stats = $s;
                                break;
                            }
                        }
                        if ($stats !== null && in_array($locale, $stats->getLocaleIDs())) {
                            if ($stats->getHandle() === '') {
                                $url = 'https://github.com/concrete5/concrete5-translations/raw/master/'.$stats->getVersion().'/'.$locale.'.'.$format;
                                $retrievedFormat = $format;
                            } else {
                                $url = 'https://github.com/concrete5/package-translations/raw/master/'.$stats->getHandle().'/'.$locale.'.po';
                                $retrievedFormat = 'po';
                            }
                            $app = isset($this->app) ? $this->app : Application::getFacadeApplication();
                            $client = $app->make('http/client');
                            $client->setUri($url);
                            $data = $client->send()->getBody();
                            if ($retrievedFormat !== $format) {
                                switch ($retrievedFormat) {
                                    case 'po':
                                        $translations = Translations::fromPoString($data);
                                        break;
                                    case 'mo':
                                        $translations = Translations::fromMoString($data);
                                        break;
                                }
                                switch ($format) {
                                    case 'po':
                                        $data = $translations->toPoString();
                                        break;
                                    case 'mo':
                                        $data = $translations->toMoString();
                                        break;
                                }
                            }
                            $result = array(
                                'stats' => $stats,
                                'format' => $format,
                                'locale' => $locale,
                                'data' => $data,
                            );
                        }
                    }
                }
            }
        }

        return $result;
    }

    public function downloadTranslations()
    {
        if ($this->token->validate('update-translations-download-translations')) {
            $data = $this->getRequestedData();
            if ($data !== null) {
                $response = Response::create($data['data']);
                $response->setPrivate();
                switch ($data['format']) {
                    case 'po':
                        $response->headers->set('Content-Disposition', 'attachment; filename=messages.po');
                        $response->headers->set('Content-Type', 'text/x-po; charset=UTF-8');
                        $response->headers->set('X-Content-Type-Options', 'nosniff');
                        break;
                    case 'mo':
                        $response->headers->set('Content-Disposition', 'attachment; filename=messages.mo');
                        $response->headers->set('Content-Type', 'application/octet-stream');
                        $response->headers->set('X-Content-Type-Options', 'nosniff');
                        break;
                }

                return $response;
            }
            $this->error->add(t('Invalid parameters'));
        } else {
            $this->error->add($this->token->getErrorMessage());
        }
        $this->view();
    }

    public function updateTranslations()
    {
        $app = isset($this->app) ? $this->app : Application::getFacadeApplication();
        $e = $app->make('error');
        if ($this->token->validate('update-translations-update-translations')) {
            $data = $this->getRequestedData('mo');
            if ($data === null) {
                $e->add(t('Invalid parameters'));
            } else {
                if ($data['stats']->getHandle() === '') {
                    $file = $this->getCorePath($data['locale'], true);
                } else {
                    $file = $this->getPackagePath($data['stats']->getHandle(), $data['locale'], true);
                }
                $directory = dirname($file);
                $fs = $app->make('Illuminate\Filesystem\Filesystem');
                if (!$fs->isDirectory($directory)) {
                    if (!$fs->makeDirectory($directory, DIRECTORY_PERMISSIONS_MODE_COMPUTED, true, true)) {
                        $e->add(t('Failed to create the local language directory'));
                    }
                }
                if (!$e->has()) {
                    if (@$fs->put($file, $data['data'])) {
                        $successMessage = t('The local translations have been correctly updated.');
                        Localization::clearCache();
                    } else {
                        if ($fs->isFile($file)) {
                            if (!$fs->isWritable($file)) {
                                $e->add(t('Failed to update the local language file: access denied'));
                            } else {
                                $e->add(t('Failed to update the local language file'));
                            }
                        } else {
                            if (!$fs->isWritable($directory)) {
                                $e->add(t('Failed to create the local language file: access denied'));
                            } else {
                                $e->add(t('Failed to create the local language file'));
                            }
                        }
                    }
                }
            }
        } else {
            $e->add($this->token->getErrorMessage());
        }

        return $e->has() ? JsonResponse::create($e) : JsonResponse::create(array('message' => $successMessage));
    }
}
