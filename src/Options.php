<?php namespace Tekton\Wordpress;

class Options implements \Tekton\Support\Contracts\ObjectCaching {

    use \Tekton\Support\Traits\ObjectPropertyCache;

    function get($name, $default = null) {
        if ($this->cache_exists($name)) {
            return $this->cache_get($name);
        }
        else {
            return $this->cache_set($name, get_option($name, $default));
        }
    }

    function set($name, $value) {
        if (update_option($name, $value)) {
            return $this->cache_set($name, $value);
        }

        return null;
    }
}
