import Office from './view/Office'
import { getCapabilities } from '@nextcloud/capabilities'

const supportedMimes = getCapabilities().officeonline.mimetypes

document.addEventListener('DOMContentLoaded', function(event) {
	if (OCA.Viewer) {
		OCA.Viewer.registerHandler({
			id: 'officeonline',
			group: null,
			mimes: supportedMimes,
			component: Office,
		})
	}
})
