<?php namespace Dynamis;

use Tekton\Support\Contracts\ValidityChecking;
use Tekton\Support\Contracts\SimpleStore;
use Dynamis\Author;
use ErrorException;
use DateTime;

class Attachment implements ValidityChecking, SimpleStore
{
    protected $id;
    protected $pathRel = '';
    protected $pathAbs = '';
    protected $local = false;

    public function __construct($object)
    {
        // Construct accepts either a URI or a database Id
        if ($object instanceof Attachment) {
            $this->id = $object->getId();
            $meta = wp_prepare_attachment_for_js($this->id);
        }
        elseif (is_numeric($object)) {
            $this->id = (int) $object;
            $meta = wp_prepare_attachment_for_js($this->id);
        }
        else {
            $this->id = 0;
            $this->set('url', $object);
        }

        // Load meta into data
        if (isset($meta) && is_array($meta)) {
            foreach ($meta as $key => $value) {
                if (in_array($key, array('date', 'modified'))) {
                    $date = new DateTime();
                    $date->setTimestamp($value);
                    $this->set($key, $date);
                }
                elseif ($key == 'author') {
                    $this->set($key, new Author($value));
                }
                else {
                    $this->set($key, $value);
                }
            }
        }

        // Mark if it's a local asset. Even if it's a remote URL we can treat it
        // like a local asset if allow_furl_open is enabled
        if (! $this->id) {
            if (is_local_file($url = $this->get('url', ''))) {
                $this->local = true;

                if (is_url($url)) {
                    $this->pathAbs = make_path($url);
                    $this->pathRel = rel_path($this->pathAbs, get_path('theme'));
                }
                else {
                    $this->pathAbs = realpath($url);
                    $this->pathRel = rel_path($this->pathAbs, get_path('theme'));
                    $this->set('url', make_url($this->pathAbs));
                }
            }
        }
    }

    public function get(string $key, $default = null)
    {
        return $this->{$key} ?? $default;
    }

    public function set(string $key, $value)
    {
        $this->{$key} = $value;

        return $this;
    }

    public function exists(string $key)
    {
        return isset($this->{$key});
    }

    public function has(string $key)
    {
        // Check if it's not empty
        if (isset($this->{$key}) && ! empty($this->{$key})) {
            return true;
        }

        return false;
    }

    public function is(string $key)
    {
        // Check if it's truthy
        if (isset($this->{$key}) && $this->{$key}) {
            return true;
        }

        return false;
    }

    public function isLocal()
    {
        return $this->local;
    }

    public function isDatabase()
    {
        return ($this->id) ? true : false;
    }

    public function isRemote()
    {
        return ! $this->isLocal();
    }

    public function getId()
    {
        return $this->id;
    }

    public function __toString()
    {
        return $this->get('url');
    }

    public function isValid()
    {
        return (! empty($this->get('url'))) ? true : false;
    }
}
