import Types from './helpers/types'
import axios from '@nextcloud/axios'
import './viewer'

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
			}
		})

		newFileMenu.addMenuEntry({
			id: 'add-' + spreadsheet.extension,
			displayName: t('officeonline', 'New Spreadsheet'),
			templateName: t('officeonline', 'New Spreadsheet') + '.' + spreadsheet.extension,
			iconClass: 'icon-filetype-spreadsheet',
			fileType: 'x-office-spreadsheet',
			actionHandler: function(filename) {
				self._createDocument(spreadsheet.mime, filename)
			}
		})

		newFileMenu.addMenuEntry({
			id: 'add-' + presentation.extension,
			displayName: t('officeonline', 'New Presentation'),
			templateName: t('officeonline', 'New Presentation') + '.' + presentation.extension,
			iconClass: 'icon-filetype-presentation',
			fileType: 'x-office-presentation',
			actionHandler: function(filename) {
				self._createDocument(presentation.mime, filename)
			}
		})
	},

	_createDocument: function(mimetype, filename) {
		const dir = document.getElementById('dir').value
		const path = dir + '/' + filename
		OCA.Files.Files.isFileNameValid(filename)
		filename = FileList.getUniqueName(filename)

		axios.post(OC.generateUrl('apps/officeonline/ajax/documents/create'), { mimetype, filename, dir }).then(({ data }) => {
			console.debug(data)
			if (data && data.status === 'success') {
				FileList.add(data.data, { animate: true, scrollTo: true })
				OCA.Viewer.open(path)
			} else {
				OC.dialogs.alert(data.data.message, t('core', 'Could not create file'))
			}
		})
	}
}

$(document).ready(function() {
	// PUBLIC SHARE LINK HANDLING

	// new file menu
	OC.Plugins.register('OCA.Files.NewFileMenu', NewFilePlugin)
})
