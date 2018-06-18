<?php namespace Dynamis;

use BadMethodCallException;
use Illuminate\Support\Arr;
use Tekton\Support\Repository;

class OptionsRepository extends Repository
{
    public function __construct()
    {
        // Do nothing, simply override parent constructor
    }

    public function reset()
    {
        throw new BadMethodCallException('You shouldn\'t reset all Wordpress options! Removing all options from the Wordpress options table would cause Wordpress to no longer function.');
    }

    public function all()
    {
        global $wpdb;
		$result = [];

        $options = $wpdb->get_results("
 			SELECT *
 			FROM  {$wpdb->options}
 		");

 		foreach ($options as $option) {
			$result[$option->option_name] = $this->get($option->option_name);
 		}

        return $result;
    }

    public function exists(string $key)
    {
        $option = $this->getOption($key);
        $key = $this->getKey($key);
        $data = get_option($option, null);

        if (! is_null($data)) {
            return (is_null($key)) ? true : Arr::exists($data, $key);
        }

        return false;
    }

    public function get(string $key, $default = null)
    {
        $option = $this->getOption($key);
        $key = $this->getKey($key);
        $data = get_option($option, null);

        if (! is_null($data)) {
            return (is_null($key)) ? $data : Arr::get($data, $key, $default);
        }

        return $default;
    }

    public function set(string $key, $value = null)
    {
        $option = $this->getOption($key);
        $key = $this->getKey($key);
        $data = $this->get($option);

        if (is_null($key)) {
            update_option($option, $value);
        }
        else {
            Arr::set($data, $key, $value);
            update_option($option, $data);
        }

        return $this;
    }

    public function remove($key)
    {
        $option = $this->getOption($key);
        $key = $this->getKey($key);
        $data = $this->get($option);

        if (is_null($key)) {
            delete_option($option);
        }
        else {
            Arr::forget($data, $key);
            update_option($option, $data);
        }

        return null;
    }

    protected function getOption($key)
    {
        $segments = explode('.', $key);
        return array_shift($segments);
    }

    protected function getKey($key)
    {
        $segments = explode('.', $key);
        $option = array_shift($segments);
        return (! empty($segments)) ? implode('.', $segments) : null;
    }
}
