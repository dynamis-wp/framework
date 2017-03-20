<?php namespace Tekton\Wordpress\Providers;

use Tekton\Support\ServiceProvider;
use Tekton\Wordpress\Post;

class HelpersProvider extends ServiceProvider {

    function register() {
        $this->app->register(\Tekton\Wordpress\Meta\Providers\MetaProvider::class);
    }

    function boot() {
        add_action('save_post', function() {
            app('cache')->forget('template_ids');
        });

        add_action('the_post', function($post_object) {
            $post_hijacks = apply_filters('automatic_post_objects', ['post' => Post::class, 'page' => Post::class]);
            $type = $post_object->post_type;

            if (in_array($type, array_keys($post_hijacks))) {
                return new $post_hijacks[$type]($post_object);
            }

            return $post_object;
        });

        add_action('template_redirect', function () {
            if (is_ssl() || WP_DEBUG) {
                return null;
            }

            if (apply_filters('force_ssl', false)) {
                $ssl_url = str_replace('http://','https://',get_the_permalink());
                wp_redirect($ssl_url);
                exit;
            }

            return null;
        });

        // Start Session, now defined as an alias
        if (apply_filters('session_start', false)) {
            app('session')->session()->start();
        }

        // Wrap CMB2
        add_action('cmb2_admin_init', function() {
            do_action('metabox_init');
        });

        // CMB2 Show on slug filter
        add_filter('cmb2_show_on', function($display, $meta_box) {
            if ( ! isset( $meta_box['show_on']['key'], $meta_box['show_on']['value'] ) ) {
                return $display;
            }

            if ( 'slug' !== $meta_box['show_on']['key'] ) {
                return $display;
            }

            $post = current_post();

            if (is_null($post)) {
                return $display;
            }

            $slug = $post->post_name;

            // See if there's a match
            return in_array($slug, (array) $meta_box['show_on']['value']);
        }, 10, 2 );


        // Improve body classes
        add_filter('body_class', function($classes) {
            // Add page slug if it doesn't exist
            if (is_single() || is_page() && !is_front_page()) {
                if (!in_array(basename(get_permalink()), $classes)) {
                    $classes[] = basename(get_permalink());
                }
            }

            // Add class if sidebar is active
            if (display_sidebar()) {
                $classes[] = 'sidebar-primary';
            }

            return $classes;
        });

        // Clean up excerpt read more text
        add_filter('excerpt_more', function() {
            return ' &hellip; <a href="' . get_permalink() . '">' . __('Continued', 'tekton-wp') . '</a>';
        });

        // Improve Archive Titles
        add_filter( 'get_the_archive_title', function ($title) {
            if ( is_category() ) {
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
