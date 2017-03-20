<?php namespace Tekton\Wordpress;

use stdClass;
use WP_Widget;
/**
 * This Widget depends on instafeed.js which is included in the bower resources
 */
abstract class Widget extends WP_Widget
{
    protected $conf;
    static $supported_data = array();

    function __construct($class, $name, $widget_options = array(), $control_options = array()) {
        $this->conf = (object) app('config')->get('widgets.'.$class, new stdClass());

        parent::__construct(
            // Base ID of widget
            str_replace('\\', '_', $class),
            // Widget name will appear in UI
            $name,
            // Widget description
            $widget_options,
            $control_options
        );
    }

    function form($instance) {
        $instance = wp_parse_args((array) $instance, array(
            'title' => '',
            'template' => '<a href="{{url}}" target="_blank"><img src="{{thumb.default}}" alt="{{title}}"/></a>',
        ));

        // Title
        echo '<p><label for="'.$this->get_field_id('title').'">Title: ';
        echo '<input class="widefat" id="'.$this->get_field_id('title').'" name="'.$this->get_field_name('title').'" type="text" value="'.esc_attr($instance['title']).'"/></label></p>';

        // Template
        $this->client_template_field($instance);
    }

    function client_template_field($instance) {
        echo '<label for="'.$this->get_field_id('template').'">Template:';
        $howto = '<span class="howto">Supported template tags: ';
        foreach (self::$supported_data as $name => $data) {
            $howto .= $data.', ';
        }
        echo substr($howto, 0, strlen($howto) - 2).'</span>';
        echo '<p><textarea class="widefat" id="'.$this->get_field_id('template').'" name="'.$this->get_field_name('template').'">'.$instance['template'].'</textarea></label></p>';
    }

    function update($new_instance, $old_instance) {
        $new_instance['title'] = sanitize_text_field($new_instance['title']);

        $instance = array_merge($old_instance, $new_instance);
        return $instance;
    }

    function convert_tag($tag, $item) {
        return $tag;
    }

    function parse_template($item, $instance) {
        $template = isset($instance['template']) ? $instance['template'] : '';

        foreach (self::$supported_data as $name => $template_tag) {
            $template = str_replace($template_tag, $this->convert_tag($tag, $item), $template);
        }

        return $template;
    }

    function widget($args, $instance) {
        echo $args['before_widget'];
        echo $args['after_widget'];
    }
}
