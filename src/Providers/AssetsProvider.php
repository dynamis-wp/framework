<?php namespace Dynamis\Providers;

use \Dynamis\ServiceProvider;

class AssetsProvider extends ServiceProvider
{
    function register()
    {
        $this->app->register(\Tekton\Assets\Providers\AssetsProvider::class);
    }

    function boot()
    {
        // Admin styles + scripts
        add_action('admin_enqueue_scripts', function() {
            foreach (app('assets')->queue('admin-styles') as $id => $item) {
                wp_enqueue_style($id, $item['asset'], $item['dependencies'], null);
            }
            foreach (app('assets')->queue('admin-scripts') as $id => $item) {
                wp_enqueue_script($id, $item['asset'], $item['dependencies'], null, true);
            }
        });

        // Admin editor styles
        add_action('after_setup_theme', function() {
            if (is_admin()) {
                foreach (app('assets')->queue('admin-editor') as $id => $item) {
                    add_editor_style($item['asset']);
                }
            }
        });

        // Theme styles + scripts
        add_action('wp_enqueue_scripts', function() {
            foreach (app('assets')->queue('styles') as $id => $item) {
                wp_enqueue_style($id, $item['asset'], $item['dependencies'], null);
            }
            foreach (app('assets')->queue('scripts') as $id => $item) {
                wp_enqueue_script($id, $item['asset'], $item['dependencies'], null, true);
            }
        }, 100);

        // Add asset_url directive
        app('blade')->directive('asset_url', function ($expression) {
            return "<?= \\asset_url($expression); ?>";
        });

        // Add asset_path directive
        app('blade')->directive('asset_path', function ($expression) {
            return "<?= \\asset_path($expression); ?>";
        });
    }
}
