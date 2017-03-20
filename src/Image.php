<?php namespace Tekton\Wordpress;

use Tekton\Wordpress\Attachment;
use Tekton\Support\Contracts\ValidityChecking;

class Image extends Attachment implements ValidityChecking {

    protected $image_meta;

    function __construct($id) {
        parent::__construct($id);

        $this->image_meta = wp_get_attachment_metadata($this->id);
    }

    function get_property($key) {
        switch ($key) {
            case 'width': return $this->get_image_meta($key);
            case 'height': return $this->get_image_meta($key);
            case 'file': return $this->get_image_meta($key);
            case 'sizes': return $this->get_image_meta($key);
            case 'image_meta': return $this->get_image_meta($key);
        }

        return parent::get_property($key);
    }

    protected function get_image_meta($name) {
        if (is_array($meta)) {
            foreach ($meta as $key => $value) {
                if ($key == $name) {
                    return $value;
                }
            }
        }

        return $return_value;
    }

    function display($size, $attr = array(), $echo = true) {
        if ( ! $this->is_valid()) {
            return '';
        }

        if ($this->id) {
            $image = wp_get_attachment_image($this->id, $size, false, $attr);
        }
        else {
            $image = '<img src="'.esc_url($this->url).'" '.parse_attributes($attr).'>';
        }

        if ($echo) {
            echo $image;
        }

        return $image;
    }

    function __invoke($size, $attr = array(), $echo = false) {
        return $this->display($size, $attr, $echo);
    }

    function __toString() {
        return $this->display('full', array());
    }

    function is_valid() {
        $url = $this->url;
        return ( ! empty($url)) ? true : false;
    }
}
