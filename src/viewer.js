import '../css/filetypes.scss'

import Office from './view/Office'
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
