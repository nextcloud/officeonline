/*
 * @copyright Copyright (c) 2019 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import Config from './../services/config'

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
