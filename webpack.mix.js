const mix = require('laravel-mix')
const config = require('./webpack.config.js')

mix.webpackConfig(Object.assign(config, {
  output: {
    chunkFilename: mix.inProduction() ? 'js/[name].[chunkhash].js' : 'js/[name].js',
    publicPath: mix.inProduction() ? '/' : 'http://127.0.0.1:8080/'
  },
}))

mix.setResourceRoot('./')

mix.copy('resources/assets/img', 'public/img')
  .copy('node_modules/font-awesome/fonts', 'public/fonts')

const vueOptions = { version: 3 }

mix.ts('resources/assets/js/app.ts', 'public/js').vue(vueOptions)
  .sass('resources/assets/sass/app.scss', 'public/css')
  .ts('resources/assets/js/remote/app.ts', 'public/js/remote').vue(vueOptions)
  .sass('resources/assets/sass/remote.scss', 'public/css')

if (mix.inProduction()) {
  mix.version()
  mix.disableNotifications()
}
