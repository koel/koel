require('events').EventEmitter.defaultMaxListeners = 30;

var elixir = require('laravel-elixir');
require('laravel-elixir-vueify');

elixir(function (mix) {
    mix.browserify('main.js');
    mix.sass('app.scss');

    mix.copy('resources/assets/img', 'public/img')
        .copy('bower_components/fontawesome/fonts', 'public/build/fonts');

    mix.scripts([
            'bower_components/plyr/dist/plyr.js'
        ], 'public/js/vendors.js', './')
        .styles([
            'resources/assets/css/**/*.css',
            'bower_components/fontawesome/css/font-awesome.min.css',
            'bower_components/plyr/dist/plyr.css'
        ], 'public/css/vendors.css', './');

    mix.version(['css/vendors.css', 'css/app.css', 'js/vendors.js', 'js/main.js']);
});
