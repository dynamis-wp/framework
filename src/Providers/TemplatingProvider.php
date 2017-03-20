<?php namespace Tekton\Wordpress\Providers;

use Tekton\Support\ServiceProvider;
use Tekton\Wordpress\Template\FileViewFinder;
use NSRosenqvist\Blade\Compiler as Blade;

class TemplatingProvider extends ServiceProvider
{
    /**
     * Bootstrap the given application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function register()
    {
        $this->registerPaths();

        $this->registerBlade();
    }

    public function boot() {
        $this->setupTemplatesPath();

        $this->setupTemplateLookup();

        $this->registerBladeDirectives();
    }

    function registerPaths() {
        $cachePath = get_path('cache').DS.'views';
        $this->app->registerPath('cache.views', $cachePath);

        if ( ! file_exists($cachePath)) {
            wp_mkdir_p($cachePath);
        }
    }

    function registerBlade() {

        $this->app->singleton('blade', function () {
            // Create folder for view cache
            $cacheDir = get_path('cache.views');

            if ( ! file_exists($cacheDir)) {
                wp_mkdir_p($cacheDir);
            }

            // Create a view finder
            $baseDirs = [get_path('stylesheet'), get_path('template')];

            $finder = new FileViewFinder($this->app->make('files'), $baseDirs);
            $finder->addNamespace('App', WP_CONTENT_DIR);

            // Create compiler
            return new Blade($cacheDir, $finder);
        });

        do_action('after_blade_setup');
    }

    function setupTemplatesPath() {
        // Customizer don't work until the theme is activated
        if (is_customize_preview() && isset($_GET['theme'])) {
            wp_die(__('Theme must be activated prior to using the customizer.', 'tekton-wp'));
        }

        // Return [theme]/templates relative paths
        add_filter('template', function ($stylesheet) {
            return dirname($stylesheet);
        });

        // Make sure the template directory is set to [theme]/templates
        if (basename($stylesheet = get_option('template')) !== 'templates') {
            update_option('template', "{$stylesheet}/templates");
            wp_redirect($_SERVER['REQUEST_URI']);
            exit();
        }
    }

    function setupTemplateLookup() {
        /**
         * Template Hierarchy should search for .blade.php files
         */
        array_map(function ($type) {
            add_filter("{$type}_template_hierarchy", function ($templates) {
                return call_user_func_array('array_merge', array_map(function ($template) {
                    $transforms = [
                        '%^/?(templates)?/?%' => config('sage.disable_option_hack') ? 'templates/' : '',
                        '%(\.blade)?(\.php)?$%' => ''
                    ];
                    $normalizedTemplate = preg_replace(array_keys($transforms), array_values($transforms), $template);
                    return ["{$normalizedTemplate}.blade.php", "{$normalizedTemplate}.php"];
                }, $templates));
            });
        }, [
            'index', '404', 'archive', 'author', 'category', 'tag', 'taxonomy', 'date', 'home',
            'frontpage', 'page', 'paged', 'search', 'single', 'singular', 'attachment'
        ]);

        /**
         * Render page using Blade
         */
        add_filter('template_include', function ($template) {
            $data = array_reduce(get_body_class(), function ($data, $class) use ($template) {
                return apply_filters("tekton_template_{$class}_data", $data, $template);
            }, []);
            echo template($template, $data);

            // Return a blank file to make WordPress happy
            return get_theme_file_path('index.php');
        }, PHP_INT_MAX);

        /**
         * Tell WordPress how to find the compiled path of comments.blade.php
         */
        add_filter('comments_template', '\\template_compiled_path');
    }

    function registerBladeDirectives() {
        // Share the global post variable to each view
        add_action('the_post', function ($post) {
            app('blade')->share('post', $post);
        });
        /**
         * Create @asset() Blade directive
         */
        app('blade')->directive('loop', function ($expression) {
            $output = '<?php while(have_posts()) : the_post(); ?>';
            $output .='<?php $post = __post($post->ID); ?>';

            return $output;
        });

        app('blade')->directive('endloop', function () {
            return '<?php endwhile; ?>';
        });

        app('blade')->directive('asset_url', function ($expression) {
            return "<?= \\asset_url({$expression}); ?>";
        });

        app('blade')->directive('asset_path', function ($expression) {
            return "<?= \\asset_path({$expression}); ?>";
        });

        app('blade')->directive('locate', function ($expression) {
            return "<?= \\get_template_part({$expression}); ?>";
        });

        /**
         * Create @field() Blade directive
         */
        app('blade')->directive('field', function ($expression) {
             return "<?php the_field({$expression}); ?>";
        });

        /**
         * Create @getField() Blade directive
         */
        app('blade')->directive('getField', function ($expression) {
            return "<?php get_field({$expression}); ?>";
        });
        //
        // $path = app('blade')->compiledPath('page.blade.php');
    }
}
