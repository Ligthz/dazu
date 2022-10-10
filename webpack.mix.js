const mix = require('laravel-mix');

var webpackConfig = {}
mix.webpackConfig(webpackConfig);

mix.alias({
    '@': '/resources'
})

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js('resources/js/auth-app.js', 'public/js/auth-app.js')
    .js('resources/js/guest-app.js', 'public/js/guest-app.js')
    .sass('resources/scss/styles.scss', 'public/css')
    .vue();
