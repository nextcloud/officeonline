/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import '../css/filetypes.scss'

import Office from './view/Office.vue'
import { getCapabilities } from '@nextcloud/capabilities'

const supportedMimes = getCapabilities().officeonline.mimetypes

if (OCA.Viewer) {
	OCA.Viewer.registerHandler({
		id: 'officeonline',
		group: null,
		mimes: supportedMimes,
		component: Office,
		theme: 'default',
	})
}
