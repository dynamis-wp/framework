<?php namespace Tekton\Wordpress\Cache;

use InvalidArgumentException;
use Illuminate\Contracts\Cache\Store;

class TransientStore implements Store
{
    use \Illuminate\Cache\RetrievesMultipleKeys;

    protected $domain;

    /**
	 * Generates a transient key.
	 *
	 * @param  string   $key     The unique key for the cache.
	 *
	 * @return string            The transient key.
	 */
	protected function generateTransientKey($key) {
		return $this->getDomain() . '_' . md5($key);
	}

    protected function getDomain() {
        if ( ! empty($this->domain)) {
            return $this->domain;
        }

        return $this->domain = str_replace('\\', '_', self::class);
    }

    protected function set($key, $value, $expiration) {
		if ( ! $key || ! is_string($key)) {
			throw new InvalidArgumentException('Invalid cache key');
		}
		if ( ! is_numeric($expiration)) {
			throw new InvalidArgumentException('Invalid expiration');
		}

		$transientKey = $this->generateTransientKey($key);
		return set_transient($transientKey, $value, $expiration * 60);
	}

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string|array  $key
     * @return mixed
     */
    public function get($key, $default = null) {
        if ( ! $key || ! is_string($key)) {
            throw new InvalidArgumentException('Invalid cache key');
        }

        $transientKey = $this->generateTransientKey($key);
        $cached = get_transient($transientKey);

        return ($cached !== false ? $cached : $default);
    }

    /**
	 * Sets an item to the cache.
	 *
	 * @param string $key        The unique key for the cache.
	 * @param mixed  $value      The data to cache.
	 * @param int    $expiration Time until expiration in seconds from now, or 0 for never expires. Ex: For one day, the expiration value would be: (60 * 60 * 24).
	 *
	 * @return void
	 */
	public function put($key, $value, $expiration) {
		$this->set($key, $value, $expiration);
	}



    /**
	 * Deletes an item from the cache
	 *
	 * @param  string $key The unique key for the cache.
	 *
	 * @return void
	 */
	public function delete($key) {
		if ( ! $key || ! is_string($key)) {
			throw new InvalidArgumentException('Invalid cache key');
		}

		$transientKey = $this->generateTransientKey($key);
		delete_transient($transientKey);
	}

	/**
	 * Deletes an item from the cache
	 *
	 * @param  string $key The unique key for the cache.
	 *
	 * @return void
	 */
    public function forget($key) {
		$this->delete($key);
	}

    /**
	 * Adds an item to cache without expiration.
	 *
	 * @param string $key        The unique key for the cache.
	 * @param mixed  $value      The data to cache.
	 *
	 * @return bool              True if added to cache
	 */
	public function forever($key, $value) {
		return self::put($key, $value, 0);
	}

    /**
     * Increment the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return int
     */
    public function increment($key, $value = 1)
    {
        $cached = self::get($key);

        if (is_numeric($cached)) {
            $cached += $value;
            $this->set($key, $cached);
			return $cached;
		}

        return false;
    }

    /**
     * Decrement the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return int
     */
    public function decrement($key, $value = 1)
    {
        return $this->increment($key, $value * -1);
    }

    /**
     * Remove all items from the cache.
     *
     * @return bool
     */
    public function flush() {
 		global $wpdb;

 		$cachedItems = $wpdb->get_results("
 			SELECT *
 			FROM  {$wpdb->options}
 			WHERE  option_name LIKE  '_transient_" . $this->getDomain() . "%'
 		");

 		foreach ($cachedItems as $item) {
 			$cachedKey = str_replace('_transient_', '', $item->option_name);
 			delete_transient($cachedKey);
 		}
 	}


    /**
     * Get the cache key prefix.
     *
     * @return string
     */
    public function getPrefix()
    {
        return '';
    }
}
