<?php namespace Dynamis;

use WP_Error;
use WP_Term;
use BadMethodCallException;
use InvalidArgumentException;
use Tekton\Support\SmartObject;
use Tekton\Support\Contracts\ValidityChecking;

class Term extends SmartObject implements ValidityChecking
{
    protected $taxonomy = null;
    protected $term = null;
    protected $id = null;
    protected $meta = [];

    public function __construct($object)
    {
        // Create from slug
        if (! is_numeric($object)) {
            $this->term = get_term_by('slug', $object, $this->taxonomy);
        }
        // Created from ID
        elseif (is_numeric($object)) {
            $this->term = get_term(intval($object), $this->taxonomy);
        }
        // Created from WP_Term
        elseif ($object instanceof WP_Term) {
            $this->term = $object;
        }
        // Created from Term
        elseif ($object instanceof Term) {
            $this->term = $object->getTerm();
        }
        else {
            $msg = get_class($this)." can only be created with either the slug, an instance of WP_Term or an instance of ".self::class;
            $msg .= " Attempted to create an instance by supplying ".(is_null($object) ? 'NULL' : 'a '.get_class($object));
            throw new InvalidArgumentException($msg);
        }

        // Set id if we retrieved the term successfully
        if ($this->term instanceof WP_Term) {
            $this->id = $this->term->term_id;
        }

        // Enable automatic lookup of term meta data
        if ($this->isValid()) {
            $this->meta = get_term_meta($this->getId());
        }
    }

    public function getId()
    {
        return $this->id;
    }

    public function getTerm()
    {
        return $this->term;
    }

    public function retrieveProperty($key = null)
    {
        // Default key
        $key = $key ?? 'name';

        // Enable user defined extra or overridden fields
        if (! is_null($result = apply_filters('term_properties', null, $key, $this))) {
            return $result;
        }

        // If the key refers to meta we can retrieve it
        if (isset($this->meta[$key])) {
            return maybe_unserialize((count($this->meta[$key]) > 1) ? $this->meta[$key] : reset($this->meta[$key]));
        }

        // Process normal keys
        switch ($key) {
            case 'name': return $this->term->name;
            case 'slug': return $this->term->slug;
            case 'taxonomy': return $this->term->taxonomy;
            case 'url': return get_term_link($this->term->term_id);
        }

        return null;
    }

    public function isValid()
    {
        // If the term is an error then we know it wasn't successful in retrieving it
        if (! $this->term instanceof WP_Term) {
            return false;
        }

        return ! empty($this->term);
    }

    // Override parent's $default with empty string
    public function get(string $key, $default = '')
    {
        return parent::get($key, $default);
    }

    // Override parent's $default with empty string
    function __invoke($key = null, $default = '')
    {
        return parent::__invoke($key, $default);
    }
}
