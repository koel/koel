require('events').EventEmitter.defaultMaxListeners = 30;

var cssnext = require('postcss-cssnext');
var elixir = require('laravel-elixir');
var gutils = require('gulp-util');

elixir.config.js.browserify.transformers.push({
  name: 'vueify',

  options: {
    postcss: [cssnext()]
  }
});

if (gutils.env._.indexOf('watch') > -1) {
  elixir.config.js.browserify.plugins.push({
    name: "browserify-hmr",
    options : {}
  });
}

elixir(function (mix) {
  mix.browserify('main.js');
  mix.sass('app.scss');

  mix.copy('resources/assets/img', 'public/img')
    .copy('node_modules/font-awesome/fonts', 'public/build/fonts');

  mix.scripts([
      'node_modules/babel-polyfill/dist/polyfill.min.js',
      'node_modules/plyr/dist/plyr.js',
      'node_modules/rangetouch/dist/rangetouch.js',
      'resources/assets/js/libs/modernizr-custom.js'
    ], 'public/js/vendors.js', './')
    .styles([
      'resources/assets/css/**/*.css',
      'node_modules/font-awesome/css/font-awesome.min.css',
      'node_modules/rangeslider.js/dist/rangeslider.css',
      'node_modules/sweetalert/dist/sweetalert.css'
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
