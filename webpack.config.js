const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = {
  mode: 'development',
  entry: './static/js/index.tsx',
  devtool: 'inline-source-map',
  module: {
    rules: [
      {
        test: /\.ts|\.tsx$/,
        use: 'ts-loader'
      },
      {
        test: /\.hbs$/,
        use: 'handlebars-loader'
      },
      {
        test: /\.scss$/,
        use: [ MiniCssExtractPlugin.loader, 'css-loader', 'sass-loader' ]
      }
    ]
  },
  resolve: {
    extensions: [ '.ts', '.tsx', '.js' ],
    alias: {
      '@views': path.resolve(__dirname, 'views'),
      '@src': path.resolve(__dirname, 'static/js')
    }
  },
  output: {
    filename: 'RedditBooru.js',
    path: path.resolve(__dirname, 'dist')
  },
  plugins: [
    new MiniCssExtractPlugin({
      filename: 'styles.css'
    })
  ]
}
