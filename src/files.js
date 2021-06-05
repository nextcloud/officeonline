import Types from './helpers/types'
import axios from '@nextcloud/axios'
import { getCapabilities } from '@nextcloud/capabilities'
import './viewer'
import Vue from 'vue'
import Office from './view/Office'

import './css/icons.css'

// eslint-disable-next-line
__webpack_nonce__ = btoa(window.OC.requestToken)

// eslint-disable-next-line
__webpack_public_path__ = window.OC.linkTo('officeonline', 'js/')

Vue.prototype.t = window.t
Vue.prototype.n = window.n
Vue.prototype.OC = window.OC
Vue.prototype.OCA = window.OCA

const NewFilePlugin = {
	attach: function(newFileMenu) {
		const self = this
		const document = Types.getFileType('document')
		const spreadsheet = Types.getFileType('spreadsheet')
		const presentation = Types.getFileType('presentation')

		newFileMenu.addMenuEntry({
			id: 'add-' + document.extension,
			displayName: t('officeonline', 'New Document'),
			templateName: t('officeonline', 'New Document') + '.' + document.extension,
			iconClass: 'icon-filetype-document',
			fileType: 'x-office-document',
			actionHandler: function(filename) {
				self._createDocument(document.mime, filename)
			},
		})

		newFileMenu.addMenuEntry({
			id: 'add-' + spreadsheet.extension,
			displayName: t('officeonline', 'New Spreadsheet'),
			templateName: t('officeonline', 'New Spreadsheet') + '.' + spreadsheet.extension,
			iconClass: 'icon-filetype-spreadsheet',
			fileType: 'x-office-spreadsheet',
			actionHandler: function(filename) {
				self._createDocument(spreadsheet.mime, filename)
			},
		})

		newFileMenu.addMenuEntry({
			id: 'add-' + presentation.extension,
			displayName: t('officeonline', 'New Presentation'),
			templateName: t('officeonline', 'New Presentation') + '.' + presentation.extension,
			iconClass: 'icon-filetype-presentation',
			fileType: 'x-office-presentation',
			actionHandler: function(filename) {
				self._createDocument(presentation.mime, filename)
			},
		})
	},

	_createDocument: function(mimetype, filename) {
		const dir = document.getElementById('dir').value
		try {
			OCA.Files.Files.isFileNameValid(filename)
		} catch (e) {
			window.OC.dialogs.alert(e, t('core', 'Could not create file'))
			return
		}
		filename = FileList.getUniqueName(filename)
		const path = dir + '/' + filename

		const isPublic = document.getElementById('isPublic') ? document.getElementById('isPublic').value === '1' : false
		if (isPublic) {
			return window.FileList.createFile(filename).then(function() {
				OCA.Viewer.open({ path })
			})
		}

		axios.post(OC.generateUrl('apps/officeonline/ajax/documents/create'), { mimetype, filename, dir }).then(({ data }) => {
			console.debug(data)
			if (data && data.status === 'success') {
				window.FileList.add(data.data, { animate: true, scrollTo: true })
				window.OCA.Viewer.open({ path })
			} else {
				window.OC.dialogs.alert(data.data.message, t('core', 'Could not create file'))
			}
		})
	},
}

document.addEventListener('DOMContentLoaded', () => {
	// PUBLIC SHARE LINK HANDLING
	const isPublic = document.getElementById('isPublic') ? document.getElementById('isPublic').value === '1' : false
	const mimetype = document.getElementById('mimetype') ? document.getElementById('mimetype').value : undefined
	const isSupportedMime = isPublic
		&& getCapabilities().officeonline.mimetypes.indexOf(mimetype) !== -1
		&& getCapabilities().officeonline.mimetypesNoDefaultOpen.indexOf(mimetype) === -1
	if (isSupportedMime) {
		/* eslint-disable-next-line no-new */
		new Vue({
			render: h => h(Office, { props: { fileName: document.getElementById('filename').value } }),
		}).$mount('#imgframe')
	}
	// new file menu
	OC.Plugins.register('OCA.Files.NewFileMenu', NewFilePlugin)
})
