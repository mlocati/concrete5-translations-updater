<?php

namespace Concrete\Package\TranslationsUpdater;

use Concrete\Core\Package\Package;
use Concrete\Core\Support\Facade\Application;
use MLocati\TranslationsUpdater\ServiceProvider;

defined('C5_EXECUTE') or die('Access Denied.');

class Controller extends Package
{
    protected $pkgHandle = 'translations_updater';

    protected $appVersionRequired = '5.7.5';

    protected $pkgVersion = '1.0.0';

    protected $pkgAutoloaderRegistries = array(
        'src' => 'MLocati\\TranslationsUpdater',
    );

    public function getPackageName()
    {
        return t('Translations Updater');
    }

    public function getPackageDescription()
    {
        return t('Update the translations of concrete5 and of some packages');
    }

    public function install()
    {
        $pkg = parent::install();
        $this->installReal('', $pkg);
    }

    public function upgrade()
    {
        $currentVersion = $this->getPackageVersion();
        parent::upgrade();
        $this->installReal($currentVersion, $this);
    }

    private function installReal($fromVersion, $pkg)
    {
        $app = isset($this->app) ? $this->app : Application::getFacadeApplication();
        $contentImporter = $app->make('Concrete\Core\Backup\ContentImporter');
        $contentImporter->importContentFile($this->getPackagePath().'/config/install.xml');
    }

    public function on_start()
    {
        $app = Application::getFacadeApplication();
        id(new ServiceProvider($app))->register();
    }
}
