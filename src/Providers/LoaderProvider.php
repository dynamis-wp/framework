<?php namespace Tekton\Wordpress\Providers;

use Tekton\Support\ServiceProvider;
use Tekton\Wordpress\Loaders\AdminLoader;
use Tekton\Wordpress\Loaders\WidgetLoader;
use Tekton\Wordpress\Loaders\BootstrapLoader;

class LoaderProvider extends ServiceProvider {

    function register() {
        // Paths
        $this->app->registerPath('bootstrap', $this->app->path().DS.'bootstrap');

        // Loaders
        $this->app->singleton('wp.bootstrap', function() {
            return new BootstrapLoader();
        });

        $this->app->singleton('wp.widgets', function() {
            return new WidgetLoader();
        });
    }

    function boot() {
        // Set up project bootstrap
        $files = glob(get_path('bootstrap').DS.'*');
        $this->app->make('wp.bootstrap')->load($files);

        // Load widgets
        add_action('widgets_init', function() {
            app('wp.widgets')->load(array_keys(app('config')->get('widgets', [])));
        });
    }
}
