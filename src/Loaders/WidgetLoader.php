<?php namespace Tekton\Wordpress\Loaders;

class WidgetLoader {

    function load($widgets) {
        if (! is_array($widgets)) {
            $widgets = array($widgets);
        }

        foreach ($widgets as $widget) {
            register_widget($widget);
        }
    }
}
