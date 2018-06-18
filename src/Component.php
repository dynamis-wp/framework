<?php namespace Dynamis;

class Component extends \Tekton\Components\Component
{
    function processData($userData = [])
    {
        if ($dataDef = $this->get('data')) {
            $data = require $dataDef;

            $data = apply_filters('component_defaults', $data, $this);
            $userData = apply_filters('component_data', $userData, $this);

            return array_replace_recursive($data, $userData);
        }

        return $userData;
    }


    function render($data = [])
    {
        $__path = $this->get('template');
        $__data = $this->processData(array_merge($data, ['component' => $this]));

        // Make sure we have a template to render
        if (! $__path) {
            return '';
        }

        return app('blade')->render($__path, $__data);
    }
}
