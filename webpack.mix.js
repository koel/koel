const mix = require('laravel-mix')
const fs = require('fs')
const { externals, resolve, plugins } = require('./webpack.config.js')

mix.webpackConfig({
  externals,
  resolve,
  plugins,
  output: {
    chunkFilename: mix.inProduction() ? 'js/[name].[chunkhash].js' : 'js/[name].js',
    publicPath: 'http://127.0.0.1:8080/'
  },
  devServer: {
    port: 8080,
    proxy: {
      '/': 'http://127.0.0.1:8000/'
    }
  }
})

mix.setResourceRoot('./')

if (mix.config.hmr) {
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

mix.ts('resources/assets/js/app.ts', 'public/js')
  .sass('resources/assets/sass/app.scss', 'public/css')
  .ts('resources/assets/js/remote/app.ts', 'public/js/remote')
  .sass('resources/assets/sass/remote.scss', 'public/css')

if (mix.inProduction()) {
  mix.version()
  mix.disableNotifications()
}
