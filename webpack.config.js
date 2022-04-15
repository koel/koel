const path = require('path')
const webpack = require('webpack')

module.exports = {
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'resources/assets/js'),
      '#': path.resolve(__dirname, 'resources/assets/sass')
    }
  },
  plugins: [
    new webpack.DefinePlugin({
      KOEL_ENV: '"web"',
      NODE_ENV: JSON.stringify(process.env.NODE_ENV || 'development')
    })
  ]
}

// test specific setups
if (process.env.NODE_ENV === 'test') {
  module.exports.externals = [require('webpack-node-externals')()]
  module.exports.devtool = 'inline-cheap-module-source-map'
}
