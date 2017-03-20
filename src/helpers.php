<?php

/**
 * @param string $file
 * @param array $data
 * @return string
 */
if (! function_exists('template'))
{
    function template($file, $data = [])
    {
        return app('blade')->render($file, $data);
    }
}

/**
 * Retrieve path to a compiled blade view
 * @param $file
 * @param array $data
 * @return string
 */
if (! function_exists('template_compiled_path'))
{
    function template_compiled_path($file, $data = [])
    {
        return app('blade')->compiledPath($file, $data);
    }
}

if (! function_exists('template_path'))
{
    function template_path($file)
    {
        return app('blade')->find($file);
    }
}

if (! function_exists('includeFile'))
{
    function includeFile($file)
    {
        include $file;
    }
}

if (! function_exists('asset_path'))
{
    function asset_path($file)
    {
        return get_path('cwd').DS.asset($file);
    }
}

if (! function_exists('asset_url'))
{
    function asset_url($file)
    {
        return get_site_url(null, asset($file));
    }
}

// Check if current post is the supplied id
if (! function_exists('post_is'))
{
    function post_is($check)
    {
        $post = current_post();

        if ( ! is_numeric($check)) {
            if ($check instanceof \Tekton\Wordpress\Post) {
                $check = $check->id;
            }
            elseif ($check instanceof \WP_Post){
                $check = $check->ID;
            }
        }

        if ( ! is_null($post) && $check == $post->ID) {
            return true;
        }

        return false;
    }
}

// Get posts that uses the specified template
if (! function_exists('get_id_by_template')) {
    function get_id_by_template($template, $first = true) {
        $template_ids = app('cache')->rememberForever('template_ids', function() {
            $template_ids = [];

            $posts = get_posts([
                'post_type' => 'page',
                'fields' => 'ids',
                'nopaging' => true,
                'meta_key' => '_wp_page_template',
                'meta_compare' => '!=',
                'meta_value' => 'default',
            ]);

            if ( ! empty($posts)) {
                $template_ids = [];

                foreach ($posts as $post_id) {
                    $template_slug = get_template_name($post_id);

                    if ( ! isset($template_ids[$template_slug])) {
                        $template_ids[$template_slug] = [];
                    }

                    $template_ids[$template_slug][] = $post_id;
                }
            }

            return $template_ids;
        });

        if (isset($template_ids[$template])) {
            if ($first && ! empty($template_ids[$template])) {
                return $template_ids[$template][0];
            }
            else {
                return $template_ids[$template];
            }
        }

        return false;
    }
}

function get_template_name($id = null) {
    $template_slug = get_page_template_slug($id);
    $template_slug = pathinfo($template_slug, PATHINFO_FILENAME);

    if (ends_with($template_slug, '.blade')) {
        $template_slug = pathinfo($template_slug, PATHINFO_FILENAME);
    }

    return $template_slug;
}

// Helper to get current post id in more than just the loop
if ( ! function_exists('current_post')) {
    function current_post() {
        global $post;

        if ($post) {
            return $post;
        }
        else {
            // Get the Post ID.
            $post_id = (isset($_GET['post'])) ? $_GET['post'] : null;
            $post_id = (isset($_POST['post_ID'])) ? $_POST['post_ID'] : $post_id;

            if ( ! is_null($post_id)) {
                return get_post($post_id);
            }
        }

        return null;
    }
}

// Convert a relative theme url to absolute
function theme_url($uri) {
    return get_uri('theme').DS.$uri;
}

// Convert a relative theme path to absolute
function theme_path($uri) {
    return get_path('theme').DS.$uri;
}

// Convert an absolute path to a relative
function theme_rel_path($uri) {
    $uri = str_replace(get_path('theme'), '', $uri);
    return ($uri[0] == '/') ? substr($uri, 1, strlen($uri)) : $uri;
}

// Convert an absolute path to a relative
function cwd_rel_path($uri) {
    $uri = str_replace(get_path('cwd'), '', $uri);
    return ($uri[0] == '/') ? substr($uri, 1, strlen($uri)) : $uri;
}

// Retrieve the page title
function page_title() {
    if (is_home()) {
        if (get_option('page_for_posts', true)) {
            return get_the_title(get_option('page_for_posts', true));
        }
        else {
            return __('Latest Posts', 'tekton-wp');
        }
    }
    elseif (is_archive()) {
        return get_the_archive_title();
    }
    elseif (is_search()) {
        return sprintf(__('Search Results for %s', 'tekton-wp'), get_search_query());
    }
    elseif (is_404()) {
        return __('Not Found', 'tekton-wp');
    }
    else {
        return get_the_title();
    }
}

// Check if current post has the provided slug
function slug_is($slug) {
    $post = current_post();

    if (is_array($slug)) {
        foreach ($slug as $ps) {
            if (slug_is($ps)) {
                return true;
            }
        }
    }
    else {
        if ( ! is_null($post) && $post->post_name == $slug) {
            return true;
        }
    }

    return false;
}

// Check if current post has the provided slug
function template_is($template) {
    return (get_template_name(current_post()) == $template) ? true : false;
}

// Check if current post is of the provided post type
function type_is($type) {
    $post = current_post();

    if (is_array($type)) {
        foreach ($type as $pt) {
            if (type_is($pt)) {
                return true;
            }
        }
    }
    else {
        if ( ! is_null($post) && $post->post_type == $type) {
            return true;
        }
    }

    return false;
}


// Wrapper for CMB2
function create_metabox($args, $cmb_styles = true) {
    $args['cmb_styles'] = $cmb_styles;
    return new_cmb2_box($args);
}

// Wrapper for CMB2-grid
function create_grid($args) {
    return new \Cmb2Grid\Grid\Cmb2Grid($args);
}

// Wrapper for CMB2
function create_group_grid(&$grid, $group_id) {
    return $grid->addCmb2GroupGrid($group_id);
}

// Get meta key
function meta_key($group, $key = '') {
    return THEME_PREFIX.$group.(!empty($key) ? '_'.$key : '');
}

function image($id = null, $size = 'large', $attr = array()) {
    if (is_null($id)) {
        $id = get_the_ID();
    }

    return new \Tekton\Wordpress\Image($id);
}

function get_page_by_slug($slug) {
    return get_page_by_path($slug);
}

function get_slug($id = null) {
    $post = (is_null($id)) ? current_post() : get_post($id);
    return $post->post_name;
}

function is_admin_ui() {
    global $pagenow;
    $admin = is_admin();

    if ($pagenow == 'admin-post.php') {
        return false;
    }

    if ($admin && defined('DOING_AJAX')) {
        return ! DOING_AJAX;
    }

    return $admin;
}

function is_admin_edit($slug = null) {
    if (is_admin_ui()) {
        if ( ! isset($_GET['action']) || $_GET['action'] != 'edit') {
            return false;
        }
        if (type_is($slug)) {
            return true;
        }
        if (slug_is($slug)) {
            return true;
        }

        return true;
    }

    return false;
}

function __post($object) {
    if ($object instanceof \WP_Query) {
        $object->the_post();
        global $post;
    }
    elseif (is_numeric($object)) {
        $post = get_post((int) $object);
    }
    else {
        global $post;
    }

    $post_hijacks = apply_filters('automatic_post_objects', ['post' => Post::class, 'page' => Post::class]);
    $type = $post->post_type;

    if (in_array($type, array_keys($post_hijacks))) {
        return new $post_hijacks[$type]($post);
    }

    return $post;
}
