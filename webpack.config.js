const path = require('path')
const webpack = require('webpack')
const Mix = require('laravel-mix').config
const plugins = require('laravel-mix').plugins
const Dotenv = require('dotenv-webpack')

/*
 |--------------------------------------------------------------------------
 | Mix Initialization
 |--------------------------------------------------------------------------
 |
 | As our first step, we'll require the project's Laravel Mix file
 | and record the user's requested compilation and build steps.
 | Once those steps have been recorded, we may get to work.
 |
 */

Mix.initialize()

/*
 |--------------------------------------------------------------------------
 | Webpack Context
 |--------------------------------------------------------------------------
 |
 | This prop will determine the appropriate context, when running Webpack.
 | Since you have the option of publishing this webpack.config.js file
 | to your project root, we will dynamically set the path for you.
 |
 */

module.exports.context = Mix.Paths.root()

/*
 |--------------------------------------------------------------------------
 | Webpack Entry
 |--------------------------------------------------------------------------
 |
 | We'll first specify the entry point for Webpack. By default, we'll
 | assume a single bundled file, but you may call Mix.extract()
 | to make a separate bundle specifically for vendor libraries.
 |
 */

module.exports.entry = Mix.entry()

/*
 |--------------------------------------------------------------------------
 | Webpack Output
 |--------------------------------------------------------------------------
 |
 | Webpack naturally requires us to specify our desired output path and
 | file name. We'll simply echo what you passed to with Mix.js().
 | Note that, for Mix.version(), we'll properly hash the file.
 |
 */

module.exports.output = Mix.output()

/*
 |--------------------------------------------------------------------------
 | Rules
 |--------------------------------------------------------------------------
 |
 | Webpack rules allow us to register any number of loaders and options.
 | Out of the box, we'll provide a handful to get you up and running
 | as quickly as possible, though feel free to add to this list.
 |
 */

module.exports.module = {
  rules: [
    {
      test: /\.vue$/,
      loader: 'vue-loader',
      options: {
        loaders: Mix.options.extractVueStyles ? {
          js: 'babel-loader' + Mix.babelConfig(),
          scss: plugins.ExtractTextPlugin.extract({
            use: 'css-loader!sass-loader',
            fallback: 'vue-style-loader'
          }),
          sass: plugins.ExtractTextPlugin.extract({
            use: 'css-loader!sass-loader?indentedSyntax',
            fallback: 'vue-style-loader'
          }),
          css: plugins.ExtractTextPlugin.extract({
            use: 'css-loader',
            fallback: 'vue-style-loader'
          })
        } : {
          js: 'babel-loader' + Mix.babelConfig(),
          scss: 'vue-style-loader!css-loader!sass-loader',
          sass: 'vue-style-loader!css-loader!sass-loader?indentedSyntax'
        },

        postcss: [
          require('autoprefixer')
        ]
      }
    },

    {
      test: /\.jsx?$/,
      exclude: /(node_modules|bower_components)/,
      loader: 'babel-loader' + Mix.babelConfig()
    },

    {
      test: /\.css$/,
      loaders: ['style-loader', 'css-loader']
    },

    {
      test: /\.html$/,
      loaders: ['html-loader']
    },

    {
      test: /\.(png|jpg|gif)$/,
      loader: 'file-loader',
      options: {
        name: 'images/[name].[ext]?[hash]',
        publicPath: '/public/'
      }
    },

    {
      test: /\.(woff2?|ttf|eot|svg|otf)$/,
      loader: 'file-loader',
      options: {
        name: 'fonts/[name].[ext]?[hash]',
        publicPath: '/public/'
      }
    }
  ]
}

if (Mix.preprocessors) {
  Mix.preprocessors.forEach(toCompile => {
    const extractPlugin = new plugins.ExtractTextPlugin(
      Mix.cssOutput(toCompile)
    )

    const sourceMap = Mix.sourcemaps ? '?sourceMap' : ''

    module.exports.module.rules.push({
      test: new RegExp(toCompile.src.path.replace(/\\/g, '\\\\') + '$'),
      use: extractPlugin.extract({
        fallback: 'style-loader',
        use: [
          { loader: 'css-loader' + sourceMap },
          { loader: 'postcss-loader' + sourceMap }
        ].concat(
          toCompile.type === 'sass' ? [
            { loader: 'resolve-url-loader' + sourceMap },
            {
              loader: 'sass-loader',
              options: Object.assign({
                precision: 8,
                outputStyle: 'expanded'
              }, toCompile.pluginOptions, { sourceMap: true })
            }
          ] : [
            {
              loader: 'less-loader' + sourceMap,
              options: toCompile.pluginOptions
            }
          ]
        )
      })
    })

    module.exports.plugins = (module.exports.plugins || []).concat(extractPlugin)
  })
} else if (Mix.options.extractVueStyles) {
  module.exports.plugins = (module.exports.plugins || []).concat(
    new plugins.ExtractTextPlugin(path.join(Mix.js.base, 'vue-styles.css'))
  )
}

/*
 |--------------------------------------------------------------------------
 | Resolve
 |--------------------------------------------------------------------------
 |
 | Here, we may set any options/aliases that affect Webpack's resolving
 | of modules. To begin, we will provide the necessary Vue alias to
 | load the Vue common library. You may delete this, if needed.
 |
 */

module.exports.resolve = {
  extensions: ['*', '.js', '.jsx', '.vue'],

  alias: {
    'vue$': 'vue/dist/vue.common.js'
  }
}

/*
 |--------------------------------------------------------------------------
 | Stats
 |--------------------------------------------------------------------------
 |
 | By default, Webpack spits a lot of information out to the terminal,
 | each you time you compile. Let's keep things a bit more minimal
 | and hide a few of those bits and pieces. Adjust as you wish.
 |
 */

module.exports.stats = {
  hash: false,
  version: false,
  timings: false,
  children: false,
  errors: false
}

module.exports.performance = { hints: false }

/*
 |--------------------------------------------------------------------------
 | Devtool
 |--------------------------------------------------------------------------
 |
 | Sourcemaps allow us to access our original source code within the
 | browser, even if we're serving a bundled script or stylesheet.
 | You may activate sourcemaps, by adding Mix.sourceMaps().
 |
 */

module.exports.devtool = Mix.sourcemaps

/*
 |--------------------------------------------------------------------------
 | Webpack Dev Server Configuration
 |--------------------------------------------------------------------------
 |
 | If you want to use that flashy hot module replacement feature, then
 | we've got you covered. Here, we'll set some basic initial config
 | for the Node server. You very likely won't want to edit this.
 |
 */
module.exports.devServer = {
  historyApiFallback: true,
  noInfo: true,
  compress: true,
  quiet: true
}

/*
 |--------------------------------------------------------------------------
 | Plugins
 |--------------------------------------------------------------------------
 |
 | Lastly, we'll register a number of plugins to extend and configure
 | Webpack. To get you started, we've included a handful of useful
 | extensions, for versioning, OS notifications, and much more.
 |
 */

module.exports.plugins = (module.exports.plugins || []).concat([
  new webpack.ProvidePlugin(Mix.autoload || {
    jQuery: 'jquery',
    $: 'jquery',
    jquery: 'jquery',
    'window.jQuery': 'jquery'
  }),

  new plugins.FriendlyErrorsWebpackPlugin(),

  new plugins.StatsWriterPlugin({
    filename: 'mix-manifest.json',
    transform: Mix.manifest.transform.bind(Mix.manifest)
  }),

  new plugins.WebpackMd5HashPlugin(),

  new webpack.LoaderOptionsPlugin({
    minimize: Mix.inProduction,
    options: {
      postcss: [
        require('autoprefixer')
      ],
      context: __dirname,
      output: { path: './' }
    }
  }),

  new Dotenv()
])

if (Mix.browserSync) {
  module.exports.plugins.push(
    new plugins.BrowserSyncPlugin(Object.assign({
      host: 'localhost',
      port: 3000,
      proxy: 'app.dev',
      files: [
        'app/**/*.php',
        'resources/views/**/*.php',
        'public/mix-manifest.json',
        'public/css/**/*.css',
        'public/js/**/*.js'
      ]
    }, Mix.browserSync))
  )
}

module.exports.plugins.push(
  new plugins.WebpackOnBuildPlugin(
    stats => Mix.events.fire('build', stats)
  )
)

if (Mix.notifications) {
  module.exports.plugins.push(
    new plugins.WebpackNotifierPlugin({
      title: 'Laravel Mix',
      alwaysNotify: true,
      contentImage: Mix.Paths.root('node_modules/laravel-mix/icons/laravel.png')
    })
  )
}

if (Mix.copy) {
  Mix.copy.forEach(copy => {
    module.exports.plugins.push(
      new plugins.CopyWebpackPlugin([copy])
    )
  })
}

if (Mix.extract) {
  module.exports.plugins.push(
    new webpack.optimize.CommonsChunkPlugin({
      names: Mix.entryBuilder.extractions.concat([
        path.join(Mix.js.base, 'manifest').replace(/\\/g, '/')
      ]),
      minChunks: Infinity
    })
  )
}

if (Mix.inProduction) {
  module.exports.plugins = module.exports.plugins.concat([
    new webpack.DefinePlugin({
      'process.env': {
        NODE_ENV: '"production"'
      }
    }),

    new webpack.optimize.UglifyJsPlugin({
      sourceMap: true,
      compress: {
        warnings: false,
        drop_console: true
      }
    })
  ])
}

/*
 |--------------------------------------------------------------------------
 | Mix Finalizing
 |--------------------------------------------------------------------------
 |
 | Now that we've declared the entirety of our Webpack configuration, the
 | final step is to scan for any custom configuration in the Mix file.
 | If mix.webpackConfig() is called, we'll merge it in, and build!
 |
 */
Mix.finalize(module.exports)
