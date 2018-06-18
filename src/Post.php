<?php namespace Dynamis;

use WP_Post;
use WP_Error;
use BadMethodCallException;
use InvalidArgumentException;
use Dynamis\Image;
use Tekton\Support\SmartObject;
use Dynamis\Author;
use Tekton\Support\Contracts\ValidityChecking;

class Post extends SmartObject implements ValidityChecking
{
    protected $post;
    protected $id;
    protected $meta = [];

    // Index = "Property", Value (array) = aliases
    protected $aliases = [
        'date' => ['published'],
        'content' => ['post_content'],
        'author' => ['post_author'],
        'image' => ['featured_image'],
    ];

    public function __construct($object)
    {
        // Set $id and $post whatever way the object is created

        // Created with a regular WP_Post
        if ($object instanceof WP_Post) {
            $this->id = (int) $object->ID;
            $this->post = $object;
        }
        // Created with a previous or subclass instance of this class
        elseif ($object instanceof Post) {
            $this->id = $object->getId();
            $this->post = $object->getPost();;
        }
        // Created with ID
        elseif (is_numeric($object)) {
            $this->id = (int) $object;
            $this->post = get_post($this->id);
        }
        else {
            $msg = get_class($this)." can only be created with either a WP_Post, an instance of ".self::class." or a post id. ";
            $msg .= "Attempted to create an instance by supplying ".(is_null($object) ? 'NULL' : 'a '.get_class($object));
            throw new InvalidArgumentException($msg);
        }

        // Enable automatic lookup of post meta data
        if ($this->isValid()) {
            $this->meta = get_post_meta($this->getId());
        }
    }

    public function getPost()
    {
        return $this->post;
    }

    public function getId()
    {
        return $this->id;
    }

    public function retrieveProperty($key = null)
    {
        $key = $key ?? 'content';

        // Enable user defined extra or overridden fields
        if (! is_null($result = apply_filters('post_properties', null, $key, $this))) {
            return $result;
        }

        // If the key refers to meta we can retrieve it
        if (isset($this->meta[$key])) {
            return maybe_unserialize((count($this->meta[$key]) > 1) ? $this->meta[$key] : reset($this->meta[$key]));
        }

        // Process normal keys
        switch ($key) {
            case 'title': return get_the_title($this->post);
            case 'url': return get_permalink($this->post);
            case 'content': return $this->getContent();
            case 'raw': return $this->post->post_content;
            case 'template': return get_template_name($this->id);
            case 'revision': return wp_is_post_revision($this->id);
            case 'password_protected': return post_password_required($this->post);
            case 'excerpt': return apply_filters('the_excerpt', get_the_excerpt($this->post));
            case 'author': return new Author(get_post_field('post_author', $this->post));
            case 'category_links': return explode('|||', get_the_category_list('|||', '', $this->id));
            case 'category': return get_the_category($this->id);
            case 'date': return make_datetime(get_the_date(DATE_ISO, $this->post), DATE_ISO);
            case 'updated': return make_datetime(get_the_modified_date(DATE_ISO, $this->post), DATE_ISO);
            case 'image': return image(get_post_thumbnail_id($this->post));
            case 'type': return get_post_type($this->post);
        }

        return null;
    }

    public function getContent($more_link_text = null, $strip_teaser = false)
    {
        // get_the_content only works with global $post
        setup_postdata($this->post);

        $content = get_the_content($more_link_text, $strip_teaser);
        $content = apply_filters('the_content', $content);

        // Reset postdata
        wp_reset_postdata();

        return $content;
    }

    public function exists(string $key)
    {
        // Check first this instance and then the original WP_Post
        return parent::exists($key) ?: isset($this->post->{$key});
    }

    public function get(string $key, $default = '')
    {
        // First check our retrieved data, then the original post object
        return parent::get($key, null) ?? $this->post->{$key} ?? $default;
    }

    // Can be invoked as a shorthand for get
    function __invoke($key = null, $default = '')
    {
        return parent::__invoke($key, $default);
    }

    public function isValid()
    {
        // If WP_Post is an error then we know it wasn't successful in retrieving the post
        if (! $this->post instanceof WP_Post) {
            return false;
        }

        // Determine if it's a valid post depending on if the ID is set or not
        return ($this->getId()) ? true : false;
    }
}
