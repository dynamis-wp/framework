<?php namespace Dynamis\Providers;

use Dynamis\OptionsRepository;
use Dynamis\ServiceProvider;

class OptionsProvider extends ServiceProvider
{
    function provides()
    {
        return ['options'];
    }

    function register()
    {
        $this->app->singleton('options', function($app) {
            return new OptionsRepository();
        });
    }

    function boot()
    {
        // Share options to blade
        app('blade')->share('options', app('options'));
    }
}
