const path = require('path')
const webpack = require('webpack')

module.exports = {
  module: {
    rules: [
      {
        test: /\.scss$/,
        loader: "sass-loader",
        options: {
          additionalData: `
          @import "#/partials/_vars.scss";
          @import "#/partials/_mixins.scss";
          `
        }
      }
    ]
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'resources/assets/js'),
      '#': path.resolve(__dirname, 'resources/assets/sass')
    }
  },
  plugins: [
    new webpack.DefinePlugin({
      KOEL_ENV: JSON.stringify(process.env.KOEL_ENV || '""')
    })
  ],
  devServer: {
    port: 8080,
    proxy: {
      '/': 'http://127.0.0.1:8000/'
    }
  }
}

// test specific setups
if (process.env.NODE_ENV === 'test') {
  module.exports.externals = [require('webpack-node-externals')()]
  module.exports.devtool = 'inline-cheap-module-source-map'
}
