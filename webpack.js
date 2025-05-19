/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

const webpackConfig = require('@nextcloud/webpack-vue-config')
const path = require('path')
const { merge } = require('webpack-merge')

const config = {
	entry: {
		viewer: path.join(__dirname, 'src', 'viewer.js'),
		files: path.join(__dirname, 'src', 'files.js'),
		document: path.join(__dirname, 'src', 'document.js'),
		admin: path.join(__dirname, 'src', 'admin.js'),
	}
}
const mergedConfig = merge(webpackConfig, config)
delete mergedConfig.entry.main
module.exports = mergedConfig
