<?php namespace Dynamis\Providers;

use Dynamis\ServiceProvider;
use Dynamis\Post;

class HelpersProvider extends ServiceProvider
{
    function boot()
    {
        // Add helper action that triggers on both save and delete
        add_action('save_post', function($id) {
            do_action('change_post', $id);
        });
        add_action('delete_post', function($id) {
            do_action('change_post', $id);
        });

        // Clear cache that's been set by helper functions
        add_action('change_post', function($id) {
            app('cache')->forget('template_ids');
            app('cache')->forget('image_sizes');
        });

        // This hook is to apply the filter 'pagination_posts_per_page' to the main
        // query in order to fix custom queries causing 404's
        add_action('pre_get_posts', function($query) {
            if ($query->is_main_query() && ! $query->is_page) {
                $posts_per_page = $query->get('posts_per_page') ?: null;
                $post_type = $query->get('post_type') ?: null;

                // Set taxonomy and term it's a taxonomy
                if ($query->is_tax) {
                    $object = get_queried_object();
                    $taxonomy = $object->taxonomy;
                    $term = $object->slug;
                }
                else {
                    $taxonomy = $term = null;
                }

                // Set posts_per_page
                if (! $query->get('nopaging')) {
                    $query->set('posts_per_page', get_posts_per_page($post_type, $posts_per_page, $taxonomy, $term));
                }
                else {
                    $query->set('posts_per_page', -1);
                }
            }
        }, 5);

        // Enable force_ssl filter
        add_action('template_redirect', function() {
            if (is_ssl() || WP_DEBUG) {
                return;
            }

            if (apply_filters('force_ssl', false)) {
                $ssl_url = str_replace('http://', 'https://', get_the_permalink());
                wp_redirect($ssl_url);
                exit;
            }
        });

        // Add global js variables
        add_action('wp_head', function() {
            echo PHP_EOL.'<script type="text/javascript">'.PHP_EOL;

            foreach (apply_filters('global_js_variables', []) as $name => $vars) {
                echo 'var '.$name.' = '.json_encode($vars).';'.PHP_EOL;
            }

            echo '</script>'.PHP_EOL;
        });

        // Add global js variables
        add_action('admin_head', function() {
            echo PHP_EOL.'<script type="text/javascript">'.PHP_EOL;

            foreach (apply_filters('admin_global_js_variables', []) as $name => $vars) {
                echo 'var '.$name.' = '.json_encode($vars).';'.PHP_EOL;
            }

            echo '</script>'.PHP_EOL;
        });

        // Start Session
        add_action('wp', function() {
            if (apply_filters('session_start', false)) {
                $session = app('session')->session();

                if (! $session->isStarted()) {
                    $session->start();
                }
                else {
                    $session->resume();
                }
            }
        }, PHP_INT_MAX);

        // Improve body classes
        add_filter('body_class', function($classes) {
            // Add page slug if it doesn't exist
            if (is_single() || is_page() && ! is_front_page()) {
                if (! in_array(basename(get_permalink()), $classes)) {
                    $classes[] = basename(get_permalink());
                }
            }

            return $classes;
        });

        // Clean up excerpt read more text
        add_filter('excerpt_more', function() {
            return ' &hellip; <a href="'.get_permalink().'">'.__('Continued', 'dynamis').'</a>';
        });

        // Improve Archive Titles
        add_filter('get_the_archive_title', function($title) {
            if (is_category()) {
                return single_cat_title('', false);
            }
            if (is_tag()) {
                return single_tag_title('', false);
            }
            if (is_author()) {
                return get_the_author();
            }
            if (is_tax()) {
                return get_queried_object()->name;
            }

            return post_type_archive_title('', false);
        });
    }
}
