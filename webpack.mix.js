const mix = require('laravel-mix')
const fs = require('fs')
const { externals, resolve, plugins } = require('./webpack.config.js')

mix.webpackConfig({ externals, resolve, plugins })
mix.setResourceRoot('./public/')

mix.config.detectHotReloading()
if (mix.config.hmr) {
  // There's a bug with Mix/copy plugin which prevents HMR from working:
  // https://github.com/JeffreyWay/laravel-mix/issues/150
  console.log('In HMR mode. If assets are missing, Ctr+C and run `yarn dev` first.')

  // Somehow public/hot isn't being removed by Mix. We'll handle it ourselves.
  process.on('SIGINT', () => {
    try {
      fs.unlinkSync(mix.config.publicPath + '/hot')
    } catch (e) {
    }
    process.exit()
  })
} else {
  mix.copy('resources/assets/img', 'public/img', false)
    .copy('node_modules/font-awesome/fonts', 'public/fonts', false)
}

mix.js('resources/assets/js/app.js', 'public/js')
  .sass('resources/assets/sass/app.scss', 'public/css')
  .js('resources/assets/js/remote/app.js', 'public/js/remote')
  .sass('resources/assets/sass/remote.scss', 'public/css')

if (mix.config.inProduction) {
  mix.version()
  mix.disableNotifications()
}
