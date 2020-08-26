const webpackConfig = require('@nextcloud/webpack-vue-config')
const path = require('path')
const { merge } = require('webpack-merge')

const config = {
	entry: {
		viewer: path.join(__dirname, 'src', 'viewer.js'),
		files: path.join(__dirname, 'src', 'files.js'),
		document: path.join(__dirname, 'src', 'document.js'),
		admin: path.join(__dirname, 'src', 'admin.js'),
	},
	output: {
		filename: '[name].js',
		jsonpFunction: 'webpackJsonpOCAOfficeOnline',
		chunkFilename: '[name].js?v=[contenthash]',
	},
	module: {
		rules: [
			{
				test: /\.(png|jpg|gif|svg)$/,
				loader: 'url-loader',
				options: {
					name: '[name].[ext]?[hash]',
				},
			},
			{
				test: /\.tsx?$/,
				use: ['babel-loader', 'ts-loader'],
				exclude: /node_modules/,
			},
		],
	},
	resolve: {
		alias: {
			vue$: 'vue/dist/vue.esm.js',
		},
		extensions: ['*', '.js', '.vue', '.json', '.tsx'],
	},
}
const mergedConfig = merge(webpackConfig, config)
delete mergedConfig.entry.main
module.exports = mergedConfig
