<?php namespace Dynamis\Providers;

use Dynamis\ServiceProvider;
use Tekton\Components\ComponentManager;
use Dynamis\Component;

class ComponentProvider extends ServiceProvider
{
    protected $defaultShortCodes = [
        'audio',
        'caption',
        'embed',
        'gallery',
        'playlist',
        'video',
    ];

    function provides()
    {
        return ['components'];
    }

    function register()
    {
        $this->app->singleton('components', function($app) {
            // Get component directory
            $directory = $this->app['config']->get('components.directory');

            // Create manager
            $manager = new ComponentManager($app, [
                'template' => 'php|blade.php',
                'styles' => 'css',
                'scripts' => 'js',
                'fields' => 'fields.php',
                'data' => 'data.php',
                'boot' => 'boot.php',
                'shortcode' => 'shortcode.php'
            ]);

            // Set up cache paths
            $this->app->registerPath('components.manifest', $cachePath = get_path('cache.dynamis').DS.'components.php');

            // Register all located components
            $components = [];

            // If we have a cached manifest we just load from it instead
            if (app_env('production') && file_exists($cachePath)) {
                $components = require $cachePath;
            }
            else {
                // Find all components
                $components = $manager->find($directory);

                // Cache file for future use if we're in production
                if (app_env('production')) {
                    write_object_to_file($cachePath, $components);
                }
            }

            // Register all components
            foreach ($components as $name => $resources) {
                // Register component in manager
                $component = new Component($resources);
                $manager->register($name, $component);
            }

            return $manager;
        });
    }

    function boot()
    {
        $this->registerComponentAssets();
        $this->registerComponentBootstrap();
        $this->registerComponentDirectives();
        $this->registerClientComponentList();
        $this->registerComponentShortcodes();
    }

    protected function registerComponentAssets()
    {
        add_action('wp_enqueue_scripts', function() {
            // Queue component assets (can be compiled to one file)
            if (app('config')->get('components.compile-assets', false) && app_env('production')) {
                // Get cache paths
                $cacheScripts = get_path('cache.dynamis').DS.'components.js';
                $cacheStyles = get_path('cache.dynamis').DS.'components.css';

                // Compile files if they don't exist yet
                if (! file_exists($cacheScripts)) {
                    $files = app('components')->resources('scripts');
                    $combined = concat_files($files);
                    write_string_to_file($cacheScripts, $combined);
                }
                if (! file_exists($cacheStyles)) {
                    $files = app('components')->resources('styles');
                    $combined = concat_files($files);
                    write_string_to_file($cacheStyles, $combined);
                }

                // Queue compiled resources
                app('assets')->queue('scripts')->add('components', make_url($cacheScripts), ['theme']);
                app('assets')->queue('styles')->add('components', make_url($cacheStyles), ['theme']);
            }
            else {
                // Queue component scripts
                foreach (app('components')->includedResources('scripts') as $name => $scripts) {
                    if (! is_array($scripts)) {
                        $scripts = [$scripts];
                    }

                    for ($i = 0; $i < count($scripts); $i++) {
                        app('assets')->queue('scripts')->add($name.'-'.$i, make_url($scripts[$i]), ['theme']);
                    }
                }

                // Queue component styles
                foreach (app('components')->includedResources('styles') as $name => $styles) {
                    if (! is_array($styles)) {
                        $styles = [$styles];
                    }

                    for ($i = 0; $i < count($styles); $i++) {
                        app('assets')->queue('styles')->add($name.'-'.$i, make_url($styles[$i]), ['theme']);
                    }
                }
            }
        });
    }

    protected function registerComponentBootstrap()
    {
        add_action('dynamis_bootstrap', function() {
            // Queue component assets (can be compiled to one file)
            if (app_env('production')) {
                // Get cache paths
                $cacheBootstrap = get_path('cache.dynamis').DS.'components.php';

                // Compile files if they don't exist yet
                if (! file_exists($cacheBootstrap)) {
                    $components = app('components')->all();
                    $files = [];

                    foreach ($components as $component) {
                        if ($component->has('boot')) {
                            $files[] = $component->get('boot');
                        }
                    }

                    $combined = concat_php_files($files);
                    write_string_to_file($cacheBootstrap, $combined);
                }

                include_global($cacheBootstrap);
            }
            else {
                $components = app('components')->all();
                $files = [];

                // Queue component bootstrap
                foreach ($components as $component) {
                    if ($component->has('boot')) {
                        $files[] = $component->get('boot');
                    }
                }

                foreach ($files as $path) {
                    include_global($path);
                }
            }
        });
    }

    protected function registerComponentDirectives()
    {
        // By default we override the default blade component directive with our own
        // but we support setting it to something else in the components config
        $directive = app('config')->get('components.component-directive', 'component');

        app('blade')->directive($directive, function ($expression) {
            return "<?= \Dynamis\Facades\Components::include($expression) ?>";
        });

        // Also enable registering each component as its own directive
        if (app('config')->get('components.directives', false)) {
            $prefix = app('config')->get('components.directive-prefix', '');

            foreach (app('components')->all() as $component) {
                $directive = str_replace('-', '_', strtolower($component->getName()));
                $directive = str_replace('.', '_', $directive);

                app('blade')->directive($prefix.$directive, function ($expression) use ($component) {
                    return "<?= \Dynamis\Facades\Components::include('".$component->getName()."', $expression) ?>";
                });
            }
        }
    }

    protected function registerClientComponentList()
    {
        // Make included components available to javascript
        add_filter('global_js_variables', function($vars) {
            $vars['includedComponents'] = [];

            foreach (app('components')->instances() as $type => $instances) {
                $vars['includedComponents'][$type] = array_keys($instances);
            }

            return $vars;
        }, 10, 1);

        // Execute scripts from components that are included on the page
        add_action('wp_footer', function() { ?>
            <script>
                (function($){
                    // Loop through all components that are included on this page
                    for (let type in includedComponents) {
                        // Loop through all instances of this component
                        for (let i in includedComponents[type]) {
                            let instance = includedComponents[type][i];
                            let context = $('#'+instance);

                            // Make sure script is defined before executing it
                            if (typeof(componentScripts) !== 'undefined' && componentScripts[type]) {
                                componentScripts[type].call(context);
                            }
                        }
                    }
                })(jQuery);
            </script><?php
        }, PHP_INT_MAX);
    }

    protected function registerComponentShortcodes()
    {
        // Enable all components to be included as shortcodes
        $prefix = app('config')->get('components.shortcode-prefix', '');
        $overrides = app('config')->get('components.shortcode-overrides', '');

        foreach (app('components')->all() as $component) {
            $name = $prefix.$component->getName();

            if (! in_array($name, $this->defaultShortCodes) || in_array($name, $overrides)) {
                add_shortcode($name, function($attr, $content = '') use ($component) {
                    // Set component arguments from shortcode attributes
                    if ($component->has('data')) {
                        $dataDef = require $component->get('data');
                    }

                    $data = shortcode_atts($dataDef ?? [], $attr);

                    // Set content to slot
                    if (empty($data['slot']) && ! empty($content)) {
                        $data['slot'] = $content;
                    }

                    // If component has a shortcode handler we pass the attributes
                    // and content on to it for it be assembled
                    if ($component->has('shortcode')) {
                        $handler = require $component->get('shortcode');
                        $assembled = $handler($data, $content) ?? false;

                        if ($assembled) {
                            $data = $assembled;
                        }
                    }

                    return app('components')->include($component, $data);
                });
            }
        }
    }
}
