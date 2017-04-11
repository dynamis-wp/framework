Tekton Wordpress
================

Tekton Wordpress is a lightweight PHP framework that integrates the power of Laravel, through [Tekton](https://gitlab.com/tekton/foundation), into Wordpress.

To get started, just require the project in your composer configuration and initialize the framework. Put the following sample code into your theme's `functions.php` to get it set up.

**Sample Code**
```php
// Autoload classes
require_once __DIR__ . '/vendor/autoload.php';

use \Tekton\Wordpress\Framework;

// Theme constants
define('THEME_PREFIX', '_mytheme_');
define('DATE_FORMAT', 'M j, Y');

Framework::instance()->init();
```

## Features
The framework integrates Laravel's Blade templating engine and moves the base template folder into the `[theme-path]/templates` sub directory.

All configuration files should be put in `[theme-path]/config` and any bootstrapping code for the theme can be put in `[theme-path]/bootstrap` from where it will be automatically loaded.
