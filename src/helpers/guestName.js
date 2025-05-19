/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Config from './../services/config.ts'
import { getCurrentUser } from '@nextcloud/auth'
import { isDirectEditing } from './mobile'

const getGuestNameCookie = function() {
	const name = 'guestUser='
	const decodedCookie = decodeURIComponent(document.cookie)
	const cookieArr = decodedCookie.split(';')
	for (let i = 0; i < cookieArr.length; i++) {
		let c = cookieArr[i]
		while (c.charAt(0) === ' ') {
			c = c.substring(1)
		}
		if (c.indexOf(name) === 0) {
			return c.substring(name.length, c.length)
		}
	}
	return ''
}

const setGuestNameCookie = function(username) {
	if (username !== '') {
		document.cookie = 'guestUser=' + encodeURIComponent(username) + '; path=/'
	}
}

const shouldAskForGuestName = () => {
	return !isDirectEditing()
		&& getCurrentUser() === null
		&& Config.get('userId') === null
		&& getGuestNameCookie() === ''
		&& (Config.get('permissions') & OC.PERMISSION_UPDATE)
}

export {
	getGuestNameCookie,
	setGuestNameCookie,
	shouldAskForGuestName,
}
