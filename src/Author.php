<?php namespace Dynamis;

use WP_Post;
use BadMethodCallException;
use InvalidArgumentException;
use Dynamis\Post;
use Tekton\Support\SmartObject;
use Tekton\Support\Contracts\ValidityChecking;

class Author extends SmartObject implements ValidityChecking
{
    protected $id;

    // Index = "Property", Value (array) = aliases
    protected $aliases = [
        'url' => ['link'],
    ];

    public function __construct($object)
    {
        // Set $id whatever way the object is created

        // Created with a regular WP_Post
        if ($object instanceof WP_Post) {
            $this->id = get_post_field('post_author', $object);
        }
        // Created with a regular Post
        if ($object instanceof Post) {
            $this->id = get_post_field('post_author', $object->getId());
        }
        // Created with a previous or subclass instance of this class
        elseif ($object instanceof Author) {
            $this->id = $object->getId();
        }
        // Created with ID
        elseif (is_numeric($object)) {
            $this->id = (int) $object;
        }
        else {
            $msg = get_class($this)." can only be created with either a WP_Post, an instance of ".self::class." or an author id. ";
            $msg .= "Attempted to create an instance by supplying ".(is_null($object) ? 'NULL' : 'a '.get_class($object));
            throw new InvalidArgumentException($msg);
        }
    }

    public function getId()
    {
        return $this->id;
    }

    public function retrieveProperty($key = null)
    {
        $key = $key ?? 'name';

        // Enable user defined extra or overridden fields
        if (! is_null($result = apply_filters('author_properties', null, $key, $this))) {
            return $result;
        }

        // Process normal keys
        switch ($key) {
            case 'name': return get_the_author_meta('display_name', $this->id);
            case 'first_name': return get_the_author_meta('first_name', $this->id);
            case 'last_name': return get_the_author_meta('last_name', $this->id);
            case 'nickname': return get_the_author_meta('nickname', $this->id);
            case 'email': return get_the_author_meta('user_email', $this->id);
            case 'website': return get_the_author_meta('user_url', $this->id);
            case 'url': return get_author_posts_url($this->id);
        }

        return null;
    }

    // Can be invoked as a shorthand for get
    function __invoke($key = null, $default = '')
    {
        return parent::__invoke($key, $default);
    }

    // Can be invoked as a shorthand for get
    function __toString()
    {
        return $this->get('name');
    }

    public function isValid()
    {
        // Determine if it's a valid author depending on if the ID is set or not
        return ($this->getId()) ? true : false;
    }
}
