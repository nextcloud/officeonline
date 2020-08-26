import Office from './view/Office'

const supportedMimes = OC.getCapabilities().officeonline.mimetypes.concat(OC.getCapabilities().officeonline.mimetypesNoDefaultOpen)

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
