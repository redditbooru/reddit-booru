const baseConfig = require('./webpack.config');

module.exports = {
  ...baseConfig,
  mode: 'production',
  devtool: 'source-map'
};
