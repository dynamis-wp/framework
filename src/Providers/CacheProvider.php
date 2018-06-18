<?php namespace Dynamis\Providers;

use Dynamis\ServiceProvider;
use Dynamis\Cache\TransientStore;

class CacheProvider extends ServiceProvider
{
    protected $session;

    protected function getSession()
    {
        if (! $this->session) {
            $this->session = app('session')->segment(self::class);
        }

        return $this->session;
    }

    public function register()
    {
        $this->registerTransientStore();

        // Register content cache paths
        $this->app->registerPath('cache.content', ensure_dir_exists(get_path('cache').DS.'content'));
        $this->app->registerUri('cache.content', get_uri('cache').DS.'content');
    }

    public function boot()
    {
        $this->registerCacheClearing();
        $this->registerContentCache();
    }

    protected function registerContentCache()
    {
        // Delete content cache files
        add_action('change_post', function($id) {
            foreach (glob(get_path('cache.content').DS.'post-'.$id.'-*.html') as $cachePath) {
                unlink($cachePath);
            }
        });

        // Enable caching post content by hooking into the post_properties filter
        if (app('config')->get('app.content-cache', false) && app_env('production')) {
            // Hook into filter
            add_filter('post_properties', function($value, $key, $post) {
                if ($key != 'content') {
                    return $value;
                }

                // Don't load from cache if the post is password protected or if
                // it's being previewed
                if (is_preview() || post_password_required($original = $post->getPost())) {
                    return $value;
                }
                else {
                    // Get page number
                    setup_postdata($original);

                    global $pages, $page;
                    $page = ($page > count($pages)) ? count($pages) : $page;

                    wp_reset_postdata();

                    // Assemble cache path
                    $id = $post->getId();
                    $cachePath = get_path('cache.content').DS.'post-'.$id.'-'.$page.'.html';

                    // If the cache exists we load from the file instead
                    if (file_exists($cachePath)) {
                        return file_get_contents($cachePath);
                    }
                    else {
                        write_string_to_file($cachePath, $content = $post->getContent());
                        return $content;
                    }
                }
            }, 10, 3);
        }
    }

    protected function registerTransientStore()
    {
        $this->app->make('cache')->extend('transient', function ($app) {
            return $app->make('cache')->repository(new TransientStore);
        });
    }

    protected function registerCacheClearing()
    {
        // Register handlers
        add_action('admin_post_clear_cache',  [$this, 'clearCacheHandler']);
        add_action('admin_post_clear_cache_files',  [$this, 'clearCacheFilesHandler']);
        add_action('admin_post_clear_cache_transients',  [$this, 'clearCacheTransientsHandler']);

        // Add confirmation message since if it's added in onclick event WP
        // escapes the quotes and breaks the execution.
        add_filter('admin_global_js_variables', function($vars) {
            $vars['clearCacheHandler'] = [
                'confirm' => __('Are you sure you want to clear the cache? This may cause the page load time for the next visitor to be considerably longer.', 'tekton')
            ];

            return $vars;
        }, 10, 1);

        // Add toolbar button
        add_action('admin_bar_menu', function() {
            global $wp_admin_bar;
            $confirm = "return confirm(clearCacheHandler.confirm);";

            $wp_admin_bar->add_node([
                'id'      => 'cache',
                'title'   => __('Cache', 'tekton'),
                'meta'    => [
                    'class' => 'button-toolbar',
                    'title' => __('Clear cache', 'tekton'),
                ]
            ]);

            $wp_admin_bar->add_node([
                'id'    => 'cache-files',
                'parent' => 'cache',
                'title' => __('Clear Files', 'tekton'),
                'href'    => admin_url('admin-post.php').'?action=clear_cache_files',
                'meta'  => [
                    'class' => 'button-toolbar',
                    'title' => __('Clear all cached files', 'tekton'),
                    'onclick' => $confirm,
                ]
            ]);

            $wp_admin_bar->add_node([
                'id'    => 'cache-transients',
                'parent' => 'cache',
                'title' => __('Clear Transients', 'tekton'),
                'href'    => admin_url('admin-post.php').'?action=clear_cache_transients',
                'meta'  => [
                    'class' => 'button-toolbar',
                    'title' => __('Clear all cached transients', 'tekton'),
                    'onclick' => $confirm,
                ]
            ]);

            $wp_admin_bar->add_node([
                'id'    => 'cache-all',
                'parent' => 'cache',
                'title' => __('Clear All', 'tekton'),
                'href'    => admin_url('admin-post.php').'?action=clear_cache',
                'meta'  => [
                    'class' => 'button-toolbar',
                    'title' => __('Clear all cache', 'tekton'),
                    'onclick' => $confirm,
                ]
            ]);
        }, 100);

        // Show cached clear message
        $session = $this->getSession();

        if ($session->get('cleared', false)) {
            add_action('admin_notices', function () use (&$session) {
                $session->set('cleared', false);
                echo '<div class="notice notice-success is-dismissible"><p>'.__('Cache cleared!', 'tekton').'</p></div>';
            });
        }
    }

    public function clearCacheHandler()
    {
        // Clear both file and transient cache
        $this->clearCacheTransientsHandler(false);
        $this->clearCacheFilesHandler(false);

        // Redirect back
        $this->getSession()->set('cleared', true);
        wp_redirect(wp_get_referer());
        exit;
    }

    public function clearCacheTransientsHandler($redirect = true)
    {
        // Clear transients from DB
        app('cache')->flush();

        // Redirect back
        if ($redirect !== false) {
            $this->getSession()->set('cleared', true);
            wp_redirect(wp_get_referer());
            exit;
        }
    }

    public function clearCacheFilesHandler($redirect = true)
    {
        // Delete everything in cache directory
        delete_dir_contents(get_path('cache'));

        // Redirect back
        if ($redirect !== false) {
            $this->getSession()->set('cleared', true);
            wp_redirect(wp_get_referer());
            exit;
        }
    }
}
