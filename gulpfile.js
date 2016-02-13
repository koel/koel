require('events').EventEmitter.defaultMaxListeners = 30;

var elixir = require('laravel-elixir');
var gutils = require('gulp-util');
var b = elixir.config.js.browserify;

require('laravel-elixir-vueify');

if (gutils.env._.indexOf('watch') > -1) {
    b.plugins.push({
        name: "browserify-hmr",
        options : {}
    });
}

elixir(function (mix) {
    mix.browserify('main.js');
    mix.sass('app.scss');

    mix.copy('resources/assets/img', 'public/img')
        .copy('node_modules/font-awesome/fonts', 'public/build/fonts')
        .copy('resources/assets/fonts', 'public/build/fonts');

    mix.scripts([
            'bower_components/plyr/dist/plyr.js',
            'resources/assets/js/libs/modernizr-custom.js'
        ], 'public/js/vendors.js', './')
        .styles([
            'resources/assets/css/**/*.css',
            'node_modules/font-awesome/css/font-awesome.min.css',
            'node_modules/rangeslider.js/dist/rangeslider.css'
        ], 'public/css/vendors.css', './');

    mix.version(['css/vendors.css', 'css/app.css', 'js/vendors.js', 'js/main.js']);

    if (process.env.NODE_ENV !== 'production') {
        mix.browserSync({
            proxy: 'koel.dev',
            files: [
                elixir.config.get('public.css.outputFolder') + '/**/*.css',
                elixir.config.get('public.versioning.buildFolder') + '/rev-manifest.json',
            ]
        });
    }
});
