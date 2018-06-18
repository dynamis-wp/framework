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

        // If we're in production we compile the bootstrap file
        if (app_env('production')) {
            // If it's a dir we use the cached file
            if (is_dir($path)) {
                // Create cached file if it doesn't exist
                if (! file_exists($cachePath)) {
                    $files = file_search($path, '/^.*\.php$/i');
                    $combined = concat_php_files($files);
                    write_string_to_file($cachePath, $combined);
                }

                $path = $cachePath;
            }

            include_global($path);
        }
        else {
            // Glob the dir or a single file
            $files = (is_dir($path)) ? file_search($path, '/^.*\.php$/i') : [$path];

            // Include all bootstrap files
            foreach ($files as $file) {
                // Including it into the global namespace
                include_global(realpath($file));
            }
        }

        do_action('dynamis_bootstrap');
    }


}
