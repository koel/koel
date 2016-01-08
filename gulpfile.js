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
        .copy('bower_components/fontawesome/fonts', 'public/build/fonts')
        .copy('resources/assets/fonts', 'public/build/fonts');

    mix.scripts([
            'bower_components/plyr/dist/plyr.js',
            'resources/assets/js/libs/modernizr-custom.js'
        ], 'public/js/vendors.js', './')
        .styles([
            'resources/assets/css/**/*.css',
            'bower_components/fontawesome/css/font-awesome.min.css',
            'bower_components/plyr/dist/plyr.css'
        ], 'public/css/vendors.css', './');

    mix.version(['css/vendors.css', 'css/app.css', 'js/vendors.js', 'js/main.js']);

    mix.browserSync({
        files: [
            elixir.config.appPath + '/**/*.php',
            elixir.config.get('public.css.outputFolder') + '/**/*.css',
            elixir.config.get('public.versioning.buildFolder') + '/rev-manifest.json',
            'resources/views/**/*.php'
        ],
    });
});
