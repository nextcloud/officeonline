/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getLanguage, getLocale } from '@nextcloud/l10n'

const languageToBCP47 = () => {
	let language = getLanguage().replace(/_/g, '-')
	const locale = getLocale()

	// German formal should just be treated as 'de'
	if (language === 'de-DE') {
		language = 'de'
	}
	// special case where setting the bc47 region depending on the locale setting makes sense
	const whitelist = {
		de: {
			'de_CH': 'de-CH',
			'gsw': 'de-CH',
			'gsw_CH': 'de-CH',
		},
	}
	const matchingWhitelist = whitelist[language]
	if (typeof matchingWhitelist !== 'undefined' && typeof matchingWhitelist[locale] !== 'undefined') {
		return matchingWhitelist[locale]
	}

	// loleaflet expects a BCP47 language tag syntax
	// when a the nextcloud language constist of two parts we sent both
	// as the region is then provided by the language setting
	return language
}

const getNextcloudVersion = () => {
	return parseInt(OC.config.version.split('.')[0])
}

const getCurrentDirectory = () => {
	if (OCA.Sharing?.PublicApp?.fileList) {
		return OCA.Sharing.PublicApp.fileList.getCurrentDirectory()
	}

	if (OCA?.Files?.App?.currentFileList) {
		return OCA?.Files?.App?.currentFileList.getCurrentDirectory()
	}

	return ''
}

export {
	languageToBCP47,
	getNextcloudVersion,
	getCurrentDirectory,
}
