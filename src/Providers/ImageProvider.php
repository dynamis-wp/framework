<?php namespace Dynamis\Providers;

use Dynamis\ServiceProvider;
use Dynamis\Cache\TransientStore;

class ImageProvider extends ServiceProvider
{
    function register()
    {
        // Register the cache path
        $this->app->registerPath('cache.images', $cacheDir = get_path('cache').DS.'images');
        $this->app->registerUri('cache.images', get_uri('cache').DS.'images');

        ensure_dir_exists($cacheDir);

        // Register low quality image size
        add_image_size('lqip', 27, 17);
    }
}
