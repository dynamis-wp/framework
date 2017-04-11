<?php namespace Tekton\Wordpress;

use \Tekton\Framework as BaseFramework;

class Framework extends BaseFramework {

    function __construct() {
        parent::__construct();
        
        $this->container->instance('wp.framework', $this);

        if ( ! defined('TEKTON_WP_VERSION'))
            define('TEKTON_WP_VERSION', '1.0.0');
        if ( ! defined('TEKTON_WP_DIR'))
            define('TEKTON_WP_DIR', __DIR__);
        if ( ! defined('THEME_URL'))
            define('THEME_URL', preg_replace('/\/templates$/', '', get_template_directory_uri()));
        if ( ! defined('THEME_DIR'))
            define('THEME_DIR', preg_replace('/\/templates$/', '', get_template_directory()));
        if ( ! defined('THEME_PREFIX'))
            define('THEME_PREFIX', '_tekton_');

        if ( ! defined('DATE_FORMAT'))
            define('DATE_FORMAT', 'M j, Y');
    }

    function init($basePath = '', $baseUri = '') {
        if (empty($basePath)) {
            $basePath = THEME_DIR;
        }
        if (empty($baseUri)) {
            $baseUri = THEME_URL;
        }

        parent::init($basePath, $baseUri);
    }

    function registerPaths($paths = []) {
        $this->container->registerPath(array_merge([
            'cwd'        => getcwd(),
            'stylesheet' => get_stylesheet_directory(),
            'theme'      => get_stylesheet_directory(),
            'config'     => get_stylesheet_directory().DS.'config',
            'template'   => get_template_directory(),
            'upload'     => wp_upload_dir()['basedir'],
            'storage'    => wp_upload_dir()['basedir'],
            'cache'      => wp_upload_dir()['basedir'].DS.'cache',
        ], $paths));
    }

    function registerUris($uris = []) {
        $this->container->registerUri(array_merge([
            'stylesheet' => get_stylesheet_directory_uri(),
            'theme'      => get_stylesheet_directory_uri(),
            'template'   => get_template_directory_uri(),
            'upload'     => wp_upload_dir()['baseurl'],
            'storage'    => wp_upload_dir()['baseurl'],
            'cache'      => wp_upload_dir()['baseurl'].DS.'cache',
        ], $uris));
    }

    function registerCore($providers = []) {
        parent::registerCore(array_merge([
            // Core
            \Tekton\Wordpress\Providers\TransientCacheProvider::class,
            \Tekton\Wordpress\Providers\TemplatingProvider::class,
            \Tekton\Wordpress\Providers\LoaderProvider::class,
            \Tekton\Wordpress\Meta\Providers\MetaProvider::class,

            // Convenience
            \Tekton\Assets\Providers\AssetsProvider::class,
            \Tekton\Wordpress\Providers\HelpersProvider::class,
            \Tekton\Wordpress\Providers\OptionsProvider::class,
        ], $providers));
    }
}
