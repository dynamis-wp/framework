<?php namespace Tekton\Wordpress\Providers;

use Tekton\Support\ServiceProvider;
use Tekton\Wordpress\Cache\TransientStore;

class TransientCacheProvider extends ServiceProvider {

    function register() {
        $this->app->register(\Illuminate\Cache\CacheServiceProvider::class);

        $this->app->make('cache')->extend('transient', function ($app) {
            return $app->make('cache')->repository(new TransientStore);
        });
    }

    function boot() {

    }
}
