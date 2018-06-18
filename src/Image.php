<?php namespace Dynamis;

use ErrorException;
use UnexpectedValueException;
use Dynamis\Attachment;
use Dynamis\Facades\ImageLibrary;
use Illuminate\Support\Contracts\Config\Repository;

class Image extends Attachment
{
    protected $pathinfo;
    protected $dimensions = [];
    protected $alt_versions = false;

    public function __construct($id, $alt_versions = false)
    {
        // Support creating from same class
        if ($id instanceof Image) {
            $id = $id->getId();
        }

        parent::__construct($id);

        // Get default image meta
        $this->pathinfo = pathinfo($this->pathAbs);

        if ($this->id) {
            $image_meta = wp_get_attachment_metadata($this->id);

            if (is_array($image_meta)) {
                foreach ($image_meta as $key => $value) {
                    $this->set($key, $value);
                }
            }
        }

        // Make sure we have an int for width and height
        $this->set('width', $this->get('width', 0));
        $this->set('height', $this->get('height', 0));

        // If it's an image not from the database
        if ($this->isLocal()) {
            if (! $alt_versions) {
                // Save image dimensions
                list($width, $height) = getimagesize($this->pathAbs);

                $this->dimensions = [
                    'width' => $width,
                    'height' => $height,
                    'crop' => false,
                ];

                $this->set('width', $width);
                $this->set('height', $height);
            }
            else {
                // Find alternative versions of the image and base our calculations
                // on the largest's dimensions
                $versions = glob($this->pathinfo['dirname'].DS.$this->pathinfo['filename'].'*'.'.'.$this->pathinfo['extension']);
                natsort($versions);
                $versions = array_reverse($versions);
                $largest = reset($versions);

                // The paths needs to be set with the real path since the name
                // supplied might have been a path without scale suffix that
                // doesn't actually exist
                $this->pathAbs = canonicalize($largest);
                $this->pathRel = rel_path($this->pathAbs, get_path('public'));
                $this->set('url', $url = make_url($this->pathAbs));

                // Retrieve the alternate sizes from cache
                $this->alt_versions = app('cache')->rememberForever('image.versions.'.sha1($url), function() use ($largest) {
                    // Extract image info from the largest version of the image
                    list($width, $height) = getimagesize($this->pathAbs);

                    // Find out what dimensions other versions of the image should have
                    // and store them in an array that will be supplied through getSizes()
                    $regex = app('config')->get('image.scale-regex', '/@(.*?)x/s');
                    preg_match($regex, $largest, $matches);
                    $scale = (empty($matches)) ? 1 : intval(end($matches));
                    $alt_versions = [];

                    for ($i = 1; $i < intval($scale); $i++) {
                        $alt_versions[$i] = [
                            'width' => ceil(($width / $scale) * $i),
                            'height' => ceil(($height / $scale) * $i),
                            'crop' => false,
                        ];
                    }

                    // Add the largest version as well
                    $alt_versions[$scale] = [
                        'width' => $width,
                        'height' => $height,
                        'crop' => false,
                    ];

                    return $alt_versions;
                });

                // Set dimensions to the largest version
                $this->dimensions = end($this->alt_versions);
            }

            // Make sure cache is up to date
            $this->refreshLocalCache();
        }
        // Image from database
        elseif ($this->id) {
            // Save image dimensions
            $this->dimensions = [
                'width' => $this->get('width'),
                'height' => $this->get('height'),
                'crop' => false,
            ];
        }

        // TODO support remote images
        // elseif (ini_get('allow_url_fopen') && $this->isRemote()) {
    }

    protected function getSizes()
    {
        // We either get the sizes from the image itself (in case it's a @2x
        // type of image) or from the Wordpress settings

        // We only support getting sizes from local/db files so skip it if it's
        // a remote file
        if (! $this->id && $this->isRemote()) {
            return [];
        }
        else {
            if ($this->alt_versions) {
                return $this->alt_versions;
            }
            else {
                return get_all_image_sizes();
            }
        }
    }

    protected function getDimensions($size)
    {
        $size = empty($size) ? 'full' : $size;
        $sizes = $this->getSizes();

        // If full size then we return the source files info
        if ($size == 'full') {
            return $this->dimensions;
        }
        elseif (isset($sizes[$size])) {
            return $sizes[$size];
        }
        else {
            throw new UnexpectedValueException('Image size "'.$size.'" is not defined');
        }
    }

    public function getUrl($size = 'full')
    {
        // If it's full or invalid we don't need to retrieve anything
        if ($size == 'full' || is_null($size)) {
            return $this->get('url');
        }
        elseif (! $this->isValid()) {
            return '';
        }

        // If it's a theme file we might have to return a cached resized version
        if ($this->isLocal()) {
            return $this->getLocalCacheUri($size);
        }
        // Uploaded files
        elseif ($this->id) {
            return wp_get_attachment_image_src($this->id, $size)[0] ?? '';
        }
        else {
            return $this->get('url');
        }
    }

    public function render($size = null, array $attr = [], bool $defer = false)
    {
        if (! $this->isValid()) {
            return '';
        }

        // Set size
        $size = empty($size) ? 'full' : $size;

        // Local file from database
        if ($this->id) {
            $attr['srcset'] = wp_get_attachment_image_srcset($this->id, $size);
            $attr['sizes'] = wp_get_attachment_image_sizes($this->id, $size);
            $attr['src'] = wp_get_attachment_image_src($this->id, $size)[0] ?? '';

            // Set alt text
            if (! array_key_exists('alt', $attr)) {
                $attr['alt'] = get_post_meta($this->id, '_wp_attachment_image_alt', true);
            }
        }
        // Local file but not from database
        elseif ($this->isLocal()) {
            $attr['srcset'] = $this->getLocalImageSrcset();
            $attr['sizes'] = wp_calculate_image_sizes(array_values($this->dimensions), $this->get('url'));
            $attr['src'] = $this->getLocalCacheUri($size);
        }
        // File we only have a url reference to
        else {
            $attr['src'] = $this->get('url');
        }

        // Filter attributes
        $attr = $origAttr = apply_filters('image_attributes', $attr, $this, $defer);
        $attr = ($defer) ? apply_filters('deferred_image_attributes', $attr, $this) : $attr;

        // Filter image rendering
        $image = '<img '.parse_attributes($attr).'>';
        $image = apply_filters('image_rendering', $image, $this, $attr, $defer, $origAttr);
        $image = ($defer) ? apply_filters('deferred_image_rendering', $image, $this, $attr, $origAttr) : $image;

        return $image;
    }

    protected function getLocalImageSizes()
    {
        $sizes = [];

        foreach ($this->getSizes() as $size => $dimensions) {
            $cachePath = $this->getLocalCachePath($size);

            if (file_exists($cachePath)) {
                $sizes[$size] = $dimensions;
            }
        }

        return $sizes;
    }

    protected function getLocalImageSrcset()
    {
        $srcset = '';

        foreach ($this->getLocalImageSizes() as $size => $dimensions) {
            $srcset .= $this->getLocalCacheUri($size).' '.$dimensions['width'].'w, ';
        }

        $srcset .= $this->getLocalCacheUri('full').' '.$this->get('width').'w';
        return $srcset;
    }

    protected function refreshLocalCache()
    {
        if (! $this->isLocalCacheUpdated()) {
            $this->generateLocalSizes();
        }
    }

    protected function getLocalCacheUri($size)
    {
        // Return the full size url if the dimensions of the requested image are larger or the same
        if ($size == 'full' || $this->get('width') <= $this->getDimensions($size)['width']) {
            return $this->get('url');
        }

        return make_url($this->getLocalCachePath($size));
    }

    protected function isLocalCacheUpdated()
    {
        $sizes = $this->getSizes();
        $smallestSize = reset($sizes);
        $smallestKey = key($sizes);

        // If the image itself is actually smaller than the smallest size
        // then there is no use creating resized versions
        if ($this->get('width') <= $smallestSize['width']) {
            return true;
        }

        // Determine if we need to generate cached images from checking the smallest size
        $cachePath = $this->getLocalCachePath($smallestKey);

        if (! file_exists($cachePath) || filemtime($this->pathAbs) >= filemtime($cachePath)) {
            return false;
        }

        // The cached file doesn't exist or it is older than the source file
        return true;
    }

    protected function getLocalCachePath($size)
    {
        if ($size == 'full') {
            return $this->pathAbs;
        }

        $cacheName = sha1($this->pathAbs).'-'.$size.'.'.$this->pathinfo['extension'];
        return get_path('cache.images').DS.$cacheName;
    }

    public function generateLocalSizes()
    {
        // Create resized versions of the local image file
        foreach ($this->getSizes() as $size => $dimensions) {
            // Make sure we don't upscale but only make smaller versions
            if ($dimensions['width'] < $this->get('width')) {
                $cachePath = $this->getLocalCachePath($size);

                // Resize image
                $img = ImageLibrary::make($this->pathAbs);
                $img->resize($dimensions['width'], $dimensions['height'], function ($constraint) {
                    $constraint->aspectRatio();
                });

                $img->save($cachePath);
            }
        }
    }

    public function display($size, array $attr = [], bool $defer = false)
    {
        return $this->render($size, $attr, $defer);
    }

    public function __invoke($size, array $attr = [], bool $defer = false)
    {
        return $this->render($size, $attr, $defer);
    }

    public function __toString()
    {
        return $this->render('full');
    }
}
