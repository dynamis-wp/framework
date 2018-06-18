<?php namespace Dynamis;

use Tekton\Framework as BaseFramework;

class Framework extends BaseFramework
{
    public function __construct()
    {
        parent::__construct();

        $this->container->instance('dynamis', $this);

        if (! defined('DYNAMIS'))
            define('DYNAMIS', true);
        if (! defined('DYNAMIS_VERSION'))
            define('DYNAMIS_VERSION', '1.0.0');
        if (! defined('DYNAMIS_DIR'))
            define('DYNAMIS_DIR', __DIR__);
        if (! defined('THEME_URL'))
            define('THEME_URL', get_stylesheet_directory_uri());
        if (! defined('THEME_DIR'))
            define('THEME_DIR', get_stylesheet_directory());

        if (! defined('DATE_FORMAT'))
            define('DATE_FORMAT', 'M j, Y');
    }

    public function init($basePath = '', $baseUri = '')
    {
        if (empty($basePath)) {
            $basePath = THEME_DIR;
        }
        if (empty($baseUri)) {
            $baseUri = THEME_URL;
        }

        return parent::init($basePath, $baseUri);
    }

    public function registerConfig($paths = [])
    {
        parent::registerConfig(array_merge([
            get_stylesheet_directory().DS.'config',
        ], $paths));

        return $this;
    }

    public function registerPaths($paths = [])
    {
        parent::registerPaths(array_merge([
            'cwd'        => getcwd(),
            'config'     => get_stylesheet_directory().DS.'config',
            'public'     => ABSPATH,
            'stylesheet' => get_stylesheet_directory(),
            'theme'      => THEME_DIR,
            'bootstrap'  => get_stylesheet_directory().DS.'bootstrap',
            'template'   => get_template_directory(),
            'content'    => WP_CONTENT_DIR,
            'plugin'     => WP_PLUGIN_DIR,
            'upload'     => wp_upload_dir()['basedir'],
            'storage'    => wp_upload_dir()['basedir'],
            'cache'      => wp_upload_dir()['basedir'].DS.'cache',
        ], $paths));

        // Register dynamis cache path (this needs to be done after sub-framework
        // conf has been merged in order to allow the cache path to be overridable)
        $this->container->registerPath('cache.dynamis', ensure_dir_exists(get_path('cache').DS.'dynamis'));

        return $this;
    }

    public function registerUris($uris = [])
    {
        parent::registerUris(array_merge([
            'admin'      => get_admin_url(),
            'public'     => get_home_url(),
            'stylesheet' => get_stylesheet_directory_uri(),
            'theme'      => THEME_URL,
            'template'   => get_template_directory_uri(),
            'content'    => WP_CONTENT_URL,
            'plugin'     => WP_PLUGIN_URL,
            'upload'     => wp_upload_dir()['baseurl'],
            'storage'    => wp_upload_dir()['baseurl'],
            'cache'      => wp_upload_dir()['baseurl'].DS.'cache',
        ], $uris));

        // Register dynamis cache uri (this needs to be done after sub-framework
        // conf has been merged in order to allow the cache uri to be overridable)
        $this->container->registerUri('cache.dynamis', ensure_dir_exists(get_uri('cache').DS.'dynamis'));

        return $this;
    }

    public function registerCore($providers = [])
    {
        parent::registerCore(array_merge([
            // Dependencies
            \Tekton\Session\Providers\SessionProvider::class,
            \Intervention\Image\ImageServiceProviderLaravel5::class,

            // Core
            \Dynamis\Providers\ErrorHandlerProvider::class,
            \Dynamis\Providers\EventsProvider::class, // registers illuminate/events
            \Dynamis\Providers\ComponentProvider::class,
            \Dynamis\Providers\CacheProvider::class, // registers illuminate/cache
            \Dynamis\Providers\OptionsProvider::class,
            \Dynamis\Providers\TemplatingProvider::class,
            \Dynamis\Providers\ImageProvider::class,
            \Dynamis\Providers\AssetsProvider::class,
            \Dynamis\Providers\HelpersProvider::class,

            // The Theme bootstrapping support is deferred until "app: running"
            \Dynamis\Providers\BootstrapProvider::class,
        ], $providers));

        return $this;
    }

    public function clearCache()
    {
        // Clear files
        parent::clearCache();

        // Clear database
        $this->app['cache']->flush();
    }
}
