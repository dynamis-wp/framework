
### Bootstrap
Any bootstrapping code for the theme can be put in `[theme-path]/bootstrap` from where it will be automatically loaded.

### Components
Dynamis has a component system based on the single file component concept pioneered by Vue JS. Learn more about how to make your components by reading the documentation of [gulp-single-file-components](https://github.com/nsrosenqvist/gulp-single-file-components) and looking at the examples in the Dynamis Base Theme.

All registered components will also be made available as shortcodes and optionally also as widgets by installing the `dynamis/component-widget` package through composer and adding the service provider.

### Session

### Cache

### Assets

### Post

### Image

### Templating
The framework integrates Laravel's Blade templating engine and moves the base template folder into the `[theme-path]/templates` sub directory.

Dynamis has many custom blade directives to make it easier to develop Wordpress themes. There are for example `@loop` and `@queryloop` that makes it simple to loop Wordpress queries and we also make the Blade `@foreach` `$loop` variable available to you. Here below is a list of all custom directives included with Dynamis by defa
