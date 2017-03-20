<?php namespace Tekton\Wordpress\Loaders;

use \Illuminate\Contracts\Container\Container;

class BootstrapLoader
{
    function load($list) {
        foreach ($list as $file) {
            $file = realpath($file);

            if (is_dir($file)) {
                $this->load(glob($file.'/*'));
            }
            else {
                if (pathinfo($file, PATHINFO_EXTENSION) == 'php') {
                    // Including it into the global namespace
                    includeFile($file);
                }
            }
        }
    }
}
