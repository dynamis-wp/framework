<?php namespace Tekton\Wordpress\Providers;

use \Tekton\Support\ServiceProvider;
use \Tekton\Wordpress\Options;

class OptionsProvider extends ServiceProvider {

    function register() {
        $this->app->singleton('wp.options', function () {
            return new Options();
        });
    }

    function boot() {

    }
}
