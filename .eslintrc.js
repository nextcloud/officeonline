/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

module.exports = {
	extends: [
		'@nextcloud',
	],
	rules: {
		'valid-jsdoc': ['off'],
		'node/no-missing-import': ['error', {
			'tryExtensions': ['.js', '.vue', '.tsx'],
		}],
	},
	settings: {
		'import/resolver': {
			'node': {
				'extensions': [
					'.js',
					'.jsx',
					'.ts',
					'.tsx',
					'.vue',
				],
			},
		},
	},
}
