<?php namespace Dynamis\Providers;

use \Dynamis\ServiceProvider;

class EventsProvider extends ServiceProvider
{
    function register()
    {
        // Already included in Tekton\Application
        // $this->app->register(\Illuminate\Events\EventServiceProvider::class);
    }

    function boot()
    {
        add_action('plugins_loaded', function() {
            $this->app['events']->fire('wp: plugins_loaded');
        });
        add_action('setup_theme', function() {
            $this->app['events']->fire('wp: setup_theme');
        });
        add_action('after_setup_theme', function() {
            $this->app['events']->fire('wp: after_setup_theme');
        });
        add_action('init', function() {
            $this->app['events']->fire('wp: init');
        });
        add_action('widgets_init', function() {
            $this->app['events']->fire('wp: widgets_init');
        });
        add_action('register_sidebar', function() {
            $this->app['events']->fire('wp: register_sidebar');
        });
        add_action('wp_loaded', function() {
            $this->app['events']->fire('wp: wp_loaded');
        });
        add_action('admin_init', function() {
            $this->app['events']->fire('wp: admin_init');
        });
        add_action('wp', function() {
            $this->app['events']->fire('wp: wp');
        });
        add_action('wp_enqueue_scripts', function() {
            $this->app['events']->fire('wp: wp_enqueue_scripts');
        });
        add_action('admin_enqueue_scripts', function() {
            $this->app['events']->fire('wp: admin_enqueue_scripts');
        });
        add_action('wp_head', function() {
            $this->app['events']->fire('wp: wp_head');
        });
        add_action('admin_head', function() {
            $this->app['events']->fire('wp: admin_head');
        });
        add_action('wp_footer', function() {
            $this->app['events']->fire('wp: wp_footer');
        });
        add_action('admin_footer', function() {
            $this->app['events']->fire('wp: admin_footer');
        });
        add_action('shutdown', function() {
            $this->app['events']->fire('wp: shutdown');
        });
    }
}
