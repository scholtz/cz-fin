const webpack = require('webpack');
const path = require('path');

const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CompressionPlugin = require('compression-webpack-plugin');

const config = {
  entry: './frontend/src/index.js',
  output: {
    path: path.resolve(__dirname, 'htdocs/dist'),
    filename: 'js/bundle-[hash].js'
  },
  
  module: {
    rules: [
      {
        test: /\.scss$/,
        use: [
          // fallback to style-loader in development
          //process.env.NODE_ENV !== 'production'
            //? 'style-loader'
            //: MiniCssExtractPlugin.loader,
          MiniCssExtractPlugin.loader,
          'css-loader',
          'sass-loader',
        ],
      },
      {
        test: /\.css$/,
        use: [
          MiniCssExtractPlugin.loader,
          'css-loader',
        ],
      },
      { 
            test: /.(png|jpg|jpeg|gif|svg|woff|woff2|ttf|eot)$/,
            use: "url-loader?limit=100000"
      }
    ],
  },
  plugins: [
    new MiniCssExtractPlugin({
      // Options similar to the same options in webpackOptions.output
      // both options are optional
      filename: 'css/app-[hash].css',
      chunkFilename: 'css/[id]-[hash].css',
    }),
    new CompressionPlugin()
  ],
}

module.exports = config;
