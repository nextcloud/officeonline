/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Config from './../services/config.ts'

const isDirectEditing = () => Config.get('directEdit')

const isMobileInterfaceAvailable = () => window.OfficeOnlineMobileInterface
	|| (window.webkit && window.webkit.messageHandlers && window.webkit.messageHandlers.OfficeOnlineMobileInterface)

const isMobileInterfaceOnIos = () => window.webkit && window.webkit.messageHandlers && window.webkit.messageHandlers.OfficeOnlineMobileInterface

const isMobileInterfaceOnAndroid = () => window.OfficeOnlineMobileInterface

const callMobileMessage = (messageName, attributes) => {
	console.debug('callMobileMessage', messageName, attributes)
	let message = messageName
	if (typeof attributes !== 'undefined') {
		message = {
			MessageName: messageName,
			Values: attributes,
		}
	}
	let attributesString = null
	try {
		attributesString = JSON.stringify(attributes)
	} catch (e) {
		attributesString = null
	}
	// Forward to mobile handler
	if (window.OfficeOnlineMobileInterface && typeof window.OfficeOnlineMobileInterface[messageName] === 'function') {
		if (attributesString === null || typeof attributesString === 'undefined') {
			window.OfficeOnlineMobileInterface[messageName]()
		} else {
			window.OfficeOnlineMobileInterface[messageName](attributesString)
		}
	}

	// iOS webkit fallback
	if (window.webkit
		&& window.webkit.messageHandlers
		&& window.webkit.messageHandlers.OfficeOnlineMobileInterface) {
		window.webkit.messageHandlers.OfficeOnlineMobileInterface.postMessage(message)
	}
}

export default {
	isDirectEditing,
	callMobileMessage,
}

export {
	isDirectEditing,
	callMobileMessage,
	isMobileInterfaceAvailable,
	isMobileInterfaceOnAndroid,
	isMobileInterfaceOnIos,
}
