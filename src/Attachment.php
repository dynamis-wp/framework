<?php namespace Tekton\Wordpress;

use Tekton\Support\SmartObject;
use Tekton\Support\Contracts\UndefinedPropertyHandling;
use ErrorException;
use DateTime;

class Attachment extends SmartObject implements UndefinedPropertyHandling {

    public $id;
    protected $meta;

    function __construct($object) {
        if (is_numeric($object)) {
            $this->id = (int) $object;
            $this->meta = wp_prepare_attachment_for_js($this->id);
        }
        else {
            $this->id = 0;
            $this->meta = (object) array(
                'url' => $object,
            );
        }
    }

    function get_property($key) {
        switch ($key) {
            case 'title': return $this->get_meta($key);
            case 'filename': return $this->get_meta($key);
            case 'url': return $this->get_meta($key);
            case 'link': return $this->get_meta($key);
            case 'alt': return $this->get_meta($key);
            case 'author': return $this->get_meta($key);
            case 'description': return $this->get_meta($key);
            case 'caption': return $this->get_meta($key);
            case 'name': return $this->get_meta($key);
            case 'status': return $this->get_meta($key);
            case 'uploadedTo': return $this->get_meta($key);
            case 'date': return $this->get_meta($key);
            case 'modified': return $this->get_meta($key);
            case 'menuOrder': return $this->get_meta($key);
            case 'mime': return $this->get_meta($key);
            case 'type': return $this->get_meta($key);
            case 'subtype': return $this->get_meta($key);
            case 'icon': return $this->get_meta($key);
            case 'dateFormatted': return $this->get_meta($key);
            case 'nonces': return $this->get_meta($key);
            case 'editLink': return $this->get_meta($key);
            case 'width': return $this->get_meta($key);
            case 'height': return $this->get_meta($key);
            case 'sizes': return $this->get_meta($key);
            case 'fileLength': return $this->get_meta($key);
            case 'compat': return $this->get_meta($key);
            case 'thumbnail': return wp_get_attachment_thumb_file($this->id);
        }

        return parent::get_property($key);
    }

    protected function get_meta($name) {
        foreach ($this->meta as $key => $value) {
            if (in_array($key, array('date', 'modified'))) {
                $date = new DateTime();
                $date->setTimestamp($value);
                $value = $date;
            }

            if ($key == $name) {
                return $value;
            }
        }

        throw new ErrorException('Attachment meta not defined: '.$key);
    }

    function __toString() {
        return $this->get_property('url');
    }
}
