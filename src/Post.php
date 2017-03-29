<?php namespace Tekton\Wordpress;

use WP_Post;
use Tekton\Wordpress\Image;
use Tekton\Support\SmartObject;
use Tekton\Support\Contracts\ValidityChecking;

class Post extends SmartObject implements ValidityChecking {

    protected $post;
    public $id;

    function __construct($object) {
        if ($object instanceof WP_Post) {
            $this->id = (int) $object->ID;
            $this->post = $object;
        }
        elseif ($object instanceof Post) {
            $this->id = $object->id;
            $this->post = $object;
        }
        else {
            $this->id = (int) $object;
            $this->post = get_post($this->id);
        }
    }

    function get_property($key) {
        // setup_postdata($this->post);

        switch ($key) {
            case 'title': $result = get_the_title($this->post); break;
            case 'url': $result = get_permalink($this->post); break;
            case 'content': $result = do_shortcode(wpautop(get_post_field('post_content', $this->post))); break;
            case 'excerpt': $result = $this->excerpt(); break; // $result = get_the_excerpt($this->post); break;
            case 'author': $result = get_the_author_meta('display_name', get_post_field('post_author', $this->post)); break;
            case 'category_links': $result = explode('|||', get_the_category_list('|||', '', $this->id)); break;
            case 'category': $result = get_the_category($this->id); break;
            case 'published':
            case 'date': $result = make_datetime(get_the_date(DATE_ISO, $this->post), DATE_ISO); break;
            case 'updated': $result = make_datetime(get_the_modified_date(DATE_ISO, $this->post), DATE_ISO); break;
            case 'image': $result = image(get_post_thumbnail_id($this->post)); break;
            case 'type': $result = get_post_type($this->post); break;
            case 'short_url': $result = post_meta('global', 'short_url', $this->id); break;
            case 'meta_keywords': $result = post_meta('meta', 'keywords', $this->id); break;
            case 'meta_description': $result = post_meta('meta', 'description', $this->id); break;
            default: $result = null;
        }

        // wp_reset_postdata();

        if ( ! empty($result)) {
            return $result;
        }

        return parent::get_property($key);
    }

    /**
     * Used because of bugs with the wordpress excerpt function
     * @return [type] [description]
     */
    protected function excerpt() {
        if ( ! empty($this->post->post_excerpt)) {
            return $this->post->post_excerpt;
        }
        else {
            return wp_trim_words($this->post->post_content, '50');
        }
    }

    function has_excerpt() {
        return ! empty($this->post->post_excerpt);
    }

    function has_image() {
        $image = $this->image;

        if ($image instanceof Image && $image->is_valid()) {
            return true;
        }
        else {
            return false;
        }
    }

    function is_image() {
        return $this->has_image();
    }

    function is_valid() {
        return ($this->id) ? true : false;
    }
}
