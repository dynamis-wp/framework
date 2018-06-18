<?php namespace Dynamis\Providers;

use \Dynamis\ServiceProvider;
use \Rarst\wps\Plugin as WPS;

class ErrorHandlerProvider extends ServiceProvider
{
    function provides()
    {
        return ['wps'];
    }

    function register()
    {
        $this->app->singleton('wps', function () {
            return new WPS();
        });

        $this->app['wps']->run();
    }

    function boot()
    {
        // Filter out WPS from the plugin list so that the user don't accidentally
        // activate it and it messes up Wordpress (we already include it through
        // composer)
        add_filter('all_plugins', function($plugins) {
            foreach ($plugins as $key => $details) {
                if ($details['Name'] == 'wps') {
                    unset($plugins[$key]);
                }
            }
            
            return $plugins;
        }, 10, 1);
    }
}
