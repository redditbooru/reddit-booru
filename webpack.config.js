const path = require('path');

module.exports = {
  entry: './static/js/App.js',
  devtool: 'inline-source-map',
  module: {
    rules: [
      {
        test: /\.ts$/,
        use: 'ts-loader',
        exclude: /node_modules/
      },
      {
        test: /\.hbs$/,
        use: 'handlebars-loader'
      }
    ]
  },
  resolve: {
    extensions: [ '.ts', '.js' ],
    alias: {
      '@views': path.resolve(__dirname, 'views')
    }
  },
  output: {
    filename: 'RedditBooru.js',
    path: path.resolve(__dirname, 'dist')
  }
}