<?php

function component_args_from_shortcode($tag, $content)
{
    $pattern = get_shortcode_regex();

    if (preg_match_all('/'.$pattern.'/s', $content, $matches) && isset($matches[2]) && in_array($tag, $matches[2])) {
        $components = app('components');
        $component = $components->get($tag);
        $result = [];

        if ($component && $component->has('data')) {
            $dataDef = require $component->get('data');
        }

        foreach ($matches[0] as $index => $match) {
            // Set component arguments from shortcode attributes
            $atts = shortcode_parse_atts($matches[3][$index]);
            $data = (isset($dataDef)) ? shortcode_atts($dataDef, $atts) : $atts;

            // Set content to slot
            if (empty($data['slot']) && ! empty($matches[5][$index])) {
                $data['slot'] = $matches[5][$index];
            }

            $result[] = $data;
        }

        return $result;
    }

    return null;
}

function get_url_by_template($template)
{
    $id = get_id_by_template($template);
    return ($id) ? get_permalink($id) : '#';
}

function option($key = null, $default = null)
{
    if (is_null($key)) {
        return app('options')->all();
    }
    else {
        return app('options')->get($key, $default);
    }
}

function have_pages($query = null)
{
    if (is_null($query)) {
        $query = current_query();
    }

    if (! is_null($query)) {
        if ($query->is_page) {
            global $pages;
            return (count($pages ?: []) > 1) ? true : false;
        }
        else {
            return (($query->max_num_pages ?: 1) > 1) ? true : false;
        }
    }

    return false;
}

function get_posts_per_page($post_type, $posts_per_page = null, $taxonomy = null, $term = null)
{
    // Get posts_per_page
    if (is_null($posts_per_page)) {
        $posts_per_page = get_option('posts_per_page');
    }

    // Get number of post types from global filter
    $posts_per_page = apply_filters('pagination_posts_per_page', $posts_per_page, $post_type, $taxonomy, $term);

    // Sometimes taxonomy queries are without post type so we only run post type
    // specific filter when we know we got one
    if (! is_null($post_type)) {
        $posts_per_page = apply_filters('pagination_posts_per_page_'.$post_type, $posts_per_page, $taxonomy, $term);
    }

    return $posts_per_page;
}

function get_pagination_url($query, $pageNumber)
{
    if ($query->is_page) {
        $link = _wp_link_page($pageNumber);
        $anchor = new \SimpleXMLElement($link.'</a>');
        $url = $anchor['href'];
    }
    else {
        $url = get_pagenum_link($pageNumber);
    }

    return apply_filters('pagination_page_url', $url, $pageNumber, $query);
}

function get_pagination($query = null)
{
    if (is_null($query)) {
        $query = current_query();
    }

    if (! is_null($query)) {
        if ($query->is_page) {
            global $pages, $page;
            $maxPages = count($pages);
        }
        else {
            $maxPages = $query->max_num_pages ?: 1;
            $page = $query->get('paged') ?: 1;
        }

        $output = apply_filters('pagination_markup', '', $maxPages, $page, $query);

        if (empty($output)) {
            if ($query->is_page) {
                return wp_link_pages(['echo' => false]);
            }
            else {
                return get_the_posts_pagination();
            }
        }
        else {
            return $output;
        }
    }

    return null;
}

function query($args = null, $paginate = false)
{
    if ($args instanceof \WP_Query) {
        // We have to re-run get_posts if we're inserting the global pagination
        // into it otherwise paged has no effect
        if ($paginate && ! $args->get('nopaging') && ($paged = get_query_var('paged')) != $args->get('paged')) {
            $args->set('paged', $paged ?: 1);
            $args->get_posts();
        }

        return $args;
    }
    elseif (is_null($args)) {
        // Already main query
        global $wp_query;
        return $wp_query;
    }
    elseif (is_array($args)) {
        // Set paged from main query
        if ($paginate && ! ($args['nopaging'] ?? false) && ! array_key_exists('paged', $args)) {
            $args['paged'] = get_query_var('paged') ?: 1;
        }

        return new \WP_Query($args);
    }

    return null;
}

function get_all_post_types($filter = [])
{
    $postTypes = get_post_types(['public' => true], 'names', 'and');

    foreach ($postTypes as $key => $value) {
        if (in_array($value, $filter)) {
            unset($postTypes[$key]);
        }
    }

    return $postTypes;
}

function get_custom_post_types($filter = [])
{
    $postTypes = get_post_types(['public' => true, '_builtin' => false], 'names', 'and');

    foreach ($postTypes as $key => $value) {
        if (in_array($value, $filter)) {
            unset($postTypes[$key]);
        }
    }

    return $postTypes;
}

if (! function_exists('template')) {
    function template($file, $data = [])
    {
        return app('blade')->render($file, $data);
    }
}

function is_post($post = '')
{
    if (empty($post)) {
        $post = current_post();
    }

    if (! is_null($post)) {
        return (is_page($post) || is_single($post)) ? true : false;
    }

    return false;
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

if (! function_exists('asset_path'))
{
    function asset_path($file, $resolve = true)
    {
        return theme_path(($resolve) ? asset($file) : $file);
    }
}

if (! function_exists('asset_url'))
{
    function asset_url($file, $resolve = true)
    {
        return theme_url(($resolve) ? asset($file) : $file);
    }
}

// Check if current post is the supplied id
if (! function_exists('current_post_is'))
{
    function current_post_is($check)
    {
        $post = current_post();

        if (! is_numeric($check)) {
            if ($check instanceof \Dynamis\Post) {
                $check = $check->id;
            }
            elseif ($check instanceof \WP_Post){
                $check = $check->ID;
            }
        }

        if (! is_null($post) && $check == $post->ID) {
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
    function current_post($powerup = false) {
        global $post;

        if ($post) {
            return ($powerup) ? post($post) : $post;
        }
        else {
            // Get the Post ID.
            if (! is_null($post_id = current_post_id())) {
                return ($powerup) ? post($post_id) : get_post($post_id);
            }
        }

        return null;
    }
}

// Helper to get current post id in more than just the loop
if ( ! function_exists('current_post_id')) {
    function current_post_id() {
        global $post;

        if ($post) {
            return $post->ID;
        }
        else {
            // Get the Post ID.
            $post_id = (isset($_GET['post'])) ? $_GET['post'] : null;
            $post_id = (isset($_POST['post_ID'])) ? $_POST['post_ID'] : $post_id;

            if ( ! is_null($post_id)) {
                return $post_id;
            }
        }

        return null;
    }
}

// Helper to get current post id in more than just the loop
if (! function_exists('current_query')) {
    function current_query() {
        global $wp_query;

        if ($wp_query) {
            return $wp_query;
        }

        return null;
    }
}

// Convert a relative theme uri to absolute url
function theme_url($uri = '') {
    return ($uri) ? get_uri('theme').DS.$uri : get_uri('theme');
}

// Convert a relative theme uri to absolute path
function theme_path($uri = '') {
    return ($uri) ? get_path('theme').DS.$uri : get_path('theme');
}

// Convert an absolute path to a relative
function theme_rel_path($uri) {
    return rel_path($uri, get_path('theme'));
}

// Retrieve the page title
function get_page_title($post = null) {
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
        return get_the_title($post);
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
        if (! is_null($post) && $post->post_type == $type) {
            return true;
        }
    }

    return false;
}

function image($id = null, $alt_versions = false) {
    // Make sure something has been provided
    if (is_null($id)) {
        return null;
    }

    if ($id instanceof \Dynamis\Image) {
        $image = $id;
    }
    else {
        $image = new \Dynamis\Image($id, $alt_versions);
    }

    return ($image->isValid()) ? $image : null;
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

function post($object = null)
{
    // Get post however the function was called
    if ($object instanceof \WP_Query) {
        $object->the_post();
        global $post;
        $_post = $post;
    }
    elseif ($object instanceof \WP_Post) {
        $_post = $object;
    }
    elseif (is_numeric($object)) {
        $_post = get_post((int) $object);
    }
    else {
        $_post = current_post();
    }

    // Abort if can't find a post
    if (is_null($_post)) {
        return null;
    }

    // Convert post into an object
    $post_hijacks = apply_filters('automatic_post_objects', [
        'post' => \Dynamis\Post::class,
        'page' => \Dynamis\Post::class,
        'attachment' => \Dynamis\Attachment::class,
    ]);

    $type = $_post->post_type;

    if (in_array($type, array_keys($post_hijacks))) {
        $_post = new $post_hijacks[$type]($_post);
    }
    else {
        $_post = new \Dynamis\Post($_post);
    }

    return $_post;
}

function term($object)
{
    return new \Dynamis\Term($object);
}

function current_term($powerup = false)
{
    $id = get_queried_object()->term_id ?? null;

    if (! is_null($id)) {
        return ($powerup) ? new \Dynamis\Term($id) : get_term($id);
    }

    return null;
}

function get_all_image_sizes() {
    return app('cache')->rememberForever('image_sizes', function() {
        global $_wp_additional_image_sizes;

        $image_sizes = [];
        $default_image_sizes = ['thumbnail', 'medium', 'large'];

        foreach ($default_image_sizes as $size) {
            $image_sizes[$size] = [
                'width'  => intval(get_option("{$size}_size_w")),
                'height' => intval(get_option("{$size}_size_h")),
                'crop'   => get_option("{$size}_crop") ? get_option("{$size}_crop") : false,
            ];
        }

        if (isset($_wp_additional_image_sizes) && count($_wp_additional_image_sizes)) {
            $image_sizes = array_merge($image_sizes, $_wp_additional_image_sizes);
        }

        // Sort on width - ascending order
        array_multisort(array_column($image_sizes, 'width'), SORT_ASC, $image_sizes);
        return $image_sizes;
    });
}

function is_theme_asset($uri) {
    if (starts_with($uri, get_uri('theme'))) {
        return true;
    }
    elseif (starts_with($uri, get_path('theme'))) {
        return true;
    }

    return false;
}

function is_local_file($uri)
{
    if (empty($uri)) {
        return false;
    }

    $homeInfo = parse_url(home_url());
    $uriInfo = parse_url($uri);

    $home = ($homeInfo['host'] ?? '').rtrim(($homeInfo['path'] ?? '/'), '/');
    $uri = ($uriInfo['host'] ?? '').rtrim(($uriInfo['path'] ?? '/'), '/');

    if (starts_with($uri, $home) && $uri != $home) {
        return true;
    }
    elseif (starts_with($uri, ABSPATH) && $uri != ABSPATH) {
        return true;
    }

    return false;
}

function path_to_url($path)
{
    if (is_url($path)) {
        return $path;
    }

    $path = canonicalize($path);

    // Find out what directory the path is pointing to so that we can get the
    // relative path correctly no matter the wordpress configuration. Start with
    // what we assume is the bottom common denominator.
    if (starts_with($path, $theme = get_path('theme'))) {
        return get_uri('theme').'/'.rel_path($path, $theme);
    }
    if (starts_with($path, $plugin = get_path('plugin'))) {
        return get_uri('plugin').'/'.rel_path($path, $plugin);
    }
    if (starts_with($path, $upload = get_path('upload'))) {
        return get_uri('upload').'/'.rel_path($path, $upload);
    }
    if (starts_with($path, $content = get_path('content'))) {
        return get_uri('content').'/'.rel_path($path, $content);
    }
    if (starts_with($path, $public = get_path('public'))) {
        return get_uri('public').'/'.rel_path($path, $public);
    }

    return $path;
}

// This one is not perfect and can get messed up from mod rewrites
function url_to_path($url) {
    if (! is_url($url)) {
        return $url;
    }

    $url = canonicalize($url);

    // Find out what directory the url is pointing to so that we can get the
    // relative url correctly no matter the wordpress configuration. Start with
    // what we assume is the bottom common denominator.
    if (starts_with($url, $theme = get_uri('theme'))) {
        return get_path('theme').DS.rel_path($url, $theme);
    }
    if (starts_with($url, $upload = get_uri('upload'))) {
        return get_path('upload').DS.rel_path($url, $upload);
    }
    if (starts_with($url, $plugin = get_uri('plugin'))) {
        return get_path('plugin').DS.rel_path($url, $plugin);
    }
    if (starts_with($url, $content = get_uri('content'))) {
        return get_path('content').DS.rel_path($url, $content);
    }
    if (starts_with($url, $public = get_uri('public'))) {
        return get_path('public').DS.rel_path($url, $public);
    }

    return $url;
}

function make_path($url) {
    if (! is_url($url)) {
        return $url;
    }

    return url_to_path($url);
}

function make_url($path) {
    if (is_url($path)) {
        return $path;
    }

    return path_to_url($path);
}

function component($name, $data = []) {
    return \Tekton\Components\Facades\Components::include($name, $data);
}

function post_meta($key, $id = null, $default = null) {
    if (! is_numeric($id)) {
        if (is_null($id)) {
            $id = current_post_id();
        }
        elseif ($id instanceof \WP_Post) {
            $id = $id->ID;
        }
        elseif ($id instanceof \Dynamis\Post) {
            $id = $id->getId();
        }
        else {
            return null;
        }
    }

    return get_post_meta(intval($id), $key, true) ?: $default;
}

function term_meta($key, $id) {
    if (! is_numeric($id)) {
        if ($id instanceof \WP_Term) {
            $id = $id->ID;
        }
        elseif ($id instanceof \Dynamis\Term) {
            $id = $id->getId();
        }
        else {
            return null;
        }
    }

    return get_term_meta(intval($id), $key, true);
}
