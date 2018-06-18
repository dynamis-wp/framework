Dynamis Wordpress Framework
===========================

Dynamis is a lightweight component base PHP framework that integrates a modern development workflow, through [Tekton](https://github.com/tekton-php/framework), into Wordpress.

To get started, just require the project in your composer configuration and initialize the framework. Put the following sample code into your theme's `functions.php` to get it set up.

**Sample Code**
```php

// Not required but used by Tekton date helpers
define('DATE_FORMAT', 'M j, Y');

/* ------------------------------ */

// Autoload classes
require_once __DIR__ . '/vendor/autoload.php';

// Create framework
$framework = \Dynamis\Framework::getInstance();

// Configure environment
if ($stage = getenv('APP_STAGE')) {
    $framework->setEnvironment($stage);
}
else {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $framework->setEnvironment('development');
    }
    else {
        $framework->setEnvironment('production');
    }
}

// Initialize
$framework->init();
```

All configuration files will be loaded from `[theme-path]/config` but it can and should be be manually overridden to avoid having the config in a public directory:

```php
$framework->overridePath('config', 'path/to/theme/config')
```

# Documentation
The documentation and tutorials for the framework can be found here.

# License
MIT
