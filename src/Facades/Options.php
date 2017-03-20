<?php namespace Tekton\Wordpress\Facades;

class Options extends \Tekton\Support\Facade {
    protected static function getFacadeAccessor() { return 'wp.options'; }
}
