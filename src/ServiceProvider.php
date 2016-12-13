<?php
namespace MLocati\TranslationsUpdater;

use Concrete\Core\Foundation\Service\Provider;
use Zend\Http\Client\Adapter\Curl;
use Zend\Http\Client\Adapter\Socket;
use Zend\Http\Client;

/**
 * Register some commonly used service classes.
 *
 * @property \Concrete\Core\Application\Application $app
 */
class ServiceProvider extends Provider
{
    public function register()
    {
        if (!$this->app->bound('http/client')) {
            $this->app->bind('http/client', function ($app) {
                $config = $app->make('config');
                $options = [];
                $options['sslverifypeer'] = $config->get('app.curl.verifyPeer') ? true : false;
                if (function_exists('curl_init')) {
                    $options['adapter'] = Curl::class;
                } else {
                    $options['adapter'] = Socket::class;
                }

                return new Client(null, $options);
            });
        }
    }
}
