const webpack = require('webpack');
const path = require('path');

module.exports = {

    entry: {
        app: './src/app.js'
    },
    output: {
        filename: './build/bundle.js',
        sourceMapFilename: './build/bundle.map'
    },
    devtool: '#source-map',
    module: {
        rules: [{
            test: /\.jsx?$/,
            exclude: /(node_modules)/,
            loader: 'babel-loader',
            query: {
                presets: ['react', 'es2015']
            }
        }]
    }

};