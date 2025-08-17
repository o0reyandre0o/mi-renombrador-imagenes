const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = (env, argv) => {
  const isProduction = argv.mode === 'production';

  return {
    entry: {
      'admin-batch': './assets/js/admin-batch.js',
      'admin-settings': './assets/js/admin-settings.js',
      'admin-styles': './assets/sass/admin-styles.scss'
    },
    
    output: {
      path: path.resolve(__dirname, 'dist'),
      filename: 'js/[name].min.js',
      clean: true
    },

    module: {
      rules: [
        {
          test: /\.js$/,
          exclude: /node_modules/,
          use: {
            loader: 'babel-loader',
            options: {
              presets: ['@babel/preset-env']
            }
          }
        },
        {
          test: /\.scss$/,
          use: [
            MiniCssExtractPlugin.loader,
            'css-loader',
            'sass-loader'
          ]
        }
      ]
    },

    plugins: [
      new MiniCssExtractPlugin({
        filename: 'css/[name].min.css'
      })
    ],

    optimization: {
      minimize: isProduction
    },

    devtool: isProduction ? false : 'source-map',

    externals: {
      jquery: 'jQuery'
    },

    resolve: {
      alias: {
        '@': path.resolve(__dirname, 'assets/js')
      }
    }
  };
};
