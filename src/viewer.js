import Office from './view/Office'

const supportedMimes = 	OC.getCapabilities().officeonline.mimetypes.concat(OC.getCapabilities().officeonline.mimetypesNoDefaultOpen)

document.addEventListener('DOMContentLoaded', function(event) {
	// Only use it outside the files app for now
	if (typeof OCA !== 'undefined'
		&& typeof OCA.Files !== 'undefined'
		&& typeof OCA.Files.fileActions !== 'undefined'
	) {
		return
	}

	if (OCA.Viewer) {
		OCA.Viewer.registerHandler({
			id: 'officeonline',
			group: null,
			mimes: supportedMimes,
			component: Office
		})
	}
})
