<?php namespace Dynamis\Providers;

use Dynamis\ServiceProvider;
use Dynamis\Post;
use Dynamis\Template\FileViewFinder;
use NSRosenqvist\Blade\Compiler as Blade;

class TemplatingProvider extends ServiceProvider
{
    function provides()
    {
        return ['blade'];
    }

    public function register()
    {
        $this->registerPaths();
        $this->registerBlade();
    }

    public function boot()
    {
        $this->setupTemplatesPath();
        $this->setupTemplateLookup();
        $this->registerSharedObjects();
        $this->registerBladeDirectives();
    }

    function registerPaths()
    {
        $cachePath = get_path('cache').DS.'views';
        $this->app->registerPath('cache.views', ensure_dir_exists($cachePath, 0775));
    }

    function registerBlade()
    {
        $this->app->singleton('blade', function () {
            // Get Cache dir
            $cacheDir = get_path('cache.views');

            // Create a view finder
            $baseDirs = [get_path('stylesheet'), get_path('template')];

            $finder = new FileViewFinder($this->app->make('files'), $baseDirs);
            $finder->addNamespace('App', WP_CONTENT_DIR);

            $blade = new Blade($cacheDir, $finder);
            $view = null;

            // Modify blade environment
            $blade->modify(function(&$env) {
                $env->factory->setContainer($this->app);
                $env->factory->share('app', $this->app);

                $this->app->instance('view', $env->factory);
            });

            // Create compiler
            return $blade;
        });

        do_action('after_blade_setup');
    }

    function setupTemplatesPath()
    {
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

    function setupTemplateLookup()
    {
        /**
         * Template Hierarchy should search for .blade.php files
         */
        array_map(function ($type) {
            add_filter("{$type}_template_hierarchy", function ($templates) {
                return call_user_func_array('array_merge', array_map(function ($template) {
                    $transforms = [
                        '%^/?(templates)?/?%' => 'templates/',
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
        add_filter('template_include', function ($template){
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

    function registerSharedObjects()
    {
        // Share the global post variable to each view (runs after post hijacks).
        // NOTE: This only works if the template structure is built up so that the
        // views are rendered after the Wordpress loop has started
        add_action('the_post', function ($post) {
            app('blade')->share('post', $post);
        }, 9999);

        // Share config and options, others are easily accessible through app()
        app('blade')->share('config', app('config'));
    }

    function registerBladeDirectives()
    {
        // Shorthand for accessing post properties in loops
        app('blade')->directive('post', function ($expression) {
            if (app('config')->get('app.post-object', true)) {
                return '<?= $post('.$expression.'); ?>';
            }
            else {
                return '<?= $post->{'.$expression.'}; ?>';
            }
        });

        // Add loop directive
        app('blade')->directive('loop', function ($expression) {
            // Set query if expression isn't empty (same as @query)
            $output = (! empty($expression)) ? '<?php $query = query('.$expression.'); ?>' : '';

            // Get query count to populate $loop
            $output .= '<?php $loopQuery = (! isset($query)) ? current_query() : $query; ?>';
            $output .= '<?php $__currentLoopData = array_fill(0, $loopQuery->post_count, null); ?>';
            $output .= '<?php $__env->addLoop($__currentLoopData); ?>';

            // Start loop
            $output .= '<?php while ($loopQuery->have_posts()) : ?>';
            $output .= '<?php $__env->incrementLoopIndices(); ?>';
            $output .= '<?php $loop = $__env->getLastLoop(); ?>';

            // Create post
            if (app('config')->get('app.post-object', true)) {
                $output .= '<?php $post = post($loopQuery); ?>';
            }
            else {
                $output .= '<?php \\the_post($loopQuery); ?>';
            }

            return $output;
        });

        // Endloop closes the while loop and resets the loop data
        app('blade')->directive('endloop', function () {
            $output = '<?php endwhile; ?>';
            $output .= '<?php \\wp_reset_postdata(); ?>';

            // Reset $loop
            $output .= '<?php $__env->popLoop(); ?>';
            $output .= '<?php $loop = $__env->getLastLoop(); ?>';
            return $output;
        });

        // Directly set the $query variable
        app('blade')->directive('query', function ($expression) {
            return '<?php $query = query('.$expression.'); ?>';
        });

        // Display pagination. If the $query variable is set we base our pagination
        // off of that query rather than the global $query variable
        app('blade')->directive('pagination', function ($expression) {
            // Set query if expression isn't empty (same as @query)
            $output = (! empty($expression)) ? '<?php $query = query('.$expression.'); ?>' : '';

            $output .= '<?php $paginationQuery = (! isset($query)) ? current_query() : $query; ?>';
            $output .= '<?= get_pagination($paginationQuery); ?>';
            return $output;
        });

        // havepages is a @if shorthand for checking if the query has more than 1 page
        app('blade')->directive('havepages', function ($expression) {
            // Set query if expression isn't empty (same as @query)
            $output = (! empty($expression)) ? '<?php $query = query('.$expression.'); ?>' : '';

            $output .= '<?php $havepagesQuery = (! isset($query)) ? current_query() : $query; ?>';
            $output .= '<?php if (have_pages($havepagesQuery)): ?>';
            return $output;
        });

        app('blade')->directive('endhavepages', function () {
            return '<?php endif; ?>';
        });

        // resetquery doesn't actually run wp_reset_query, but only wp_reset_postdata
        // since get_posts shouldn't really be used any more, it's use is
        // considered deprecated
        app('blade')->directive('resetquery', function () {
            $output = '<?php wp_reset_postdata(); ?>';
            $output .= '<?php if (isset($query)) { unset($query); } ?>';
            return $output;
        });

        // Haveposts is a @if shorthand for checking if the loop (or queryloop)
        // have posts. To use this for a query loop the query have to first be set
        // with @query
        app('blade')->directive('haveposts', function ($expression) {
            // Set query if expression isn't empty (same as @query)
            $output = (! empty($expression)) ? '<?php $query = query('.$expression.'); ?>' : '';

            $output .= '<?php $havepostsQuery = (! isset($query)) ? current_query() : $query; ?>';
            $output .= '<?php if ($havepostsQuery->have_posts()): ?>';

            return $output;
        });

        app('blade')->directive('endhaveposts', function () {
            return '<?php endif; ?>';
        });

        // Add widget directive
        app('blade')->directive('widget', function ($expression) {
            return "<?php \\the_widget($expression); ?>";
        });

        // Add do_action directive
        app('blade')->directive('action', function ($expression) {
            return "<?php \\do_action($expression); ?>";
        });

        // Add shortcode directive
        app('blade')->directive('shortcode', function ($expression) {
            return "<?= \\do_shortcode($expression); ?>";
        });

        // Add locate directive
        app('blade')->directive('locate', function ($expression) {
            return "<?= \\get_template_part($expression); ?>";
        });

        // Add template directive
        app('blade')->directive('template', function ($expression) {
            return "<?= \\template($expression); ?>";
        });

        // Add exit directive
        app('blade')->directive('exit', function ($expression) {
            return "<?php \\wp_die($expression); ?>";
        });
        app('blade')->directive('die', function ($expression) {
            return "<?php \\wp_die($expression); ?>";
        });
    }
}
