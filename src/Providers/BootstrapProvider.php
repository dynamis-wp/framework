<?php namespace Dynamis\Providers;

use Dynamis\ServiceProvider;

class BootstrapProvider extends ServiceProvider
{
    protected $defer = true;

    function when()
    {
        return ['app: running'];
    }

    function boot()
    {
        $path = realpath(get_path('bootstrap'));
        $cachePath = get_path('cache.dynamis').DS.'bootstrap.php';

        // Glob the dir or add a single file
        $files = (is_dir($path)) ? file_search($path, '/^.*\.php$/i') : [$path];

        // Include all bootstrap files
        foreach ($files as $file) {
            // Including it into the global namespace
            include_global(realpath($file));
        }
      
        do_action('dynamis_bootstrap');
    }


}
