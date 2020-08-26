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
