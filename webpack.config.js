var path = require('path')

module.exports = {
  context: path.join(__dirname, 'resources/assets/js'),
  entry: [
  './main.js'
  ],
  module: {
    rules: [
      {
        test: /\.vue$/,
        loader: 'vue-loader',
        options: {
          // vue-loader options go here
        }
      },
      {
        test: /\.js$/,
        loader: 'babel-loader',
        exclude: /node_modules/
      },
      {
        test: /\.(png|jpg|gif|svg)$/,
        loader: 'file-loader',
        options: {
          name: '[name].[ext]?[hash]'
        }
      }
    ]
  },
  resolve: {
    alias: {
      'vue$': 'vue/dist/vue.common.js'
    }
  },
  devServer: {
    proxy: {
      contentBase: '/',
      '**': {
        target: 'http://localhost:8000',
        secure: false,
        changeOrigin: true
      }
    }
  }
}
