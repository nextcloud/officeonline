
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

const ooxml = OC.getCapabilities()['officeonline']['config']['doc_format'] === 'ooxml'

const getFileTypes = () => {
	if (ooxml) {
		return {
			document: {
				extension: 'docx',
				mime: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			},
			spreadsheet: {
				extension: 'xlsx',
				mime: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			},
			presentation: {
				extension: 'pptx',
				mime: 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
			},
		}
	}
	return {
		document: {
			extension: 'odt',
			mime: 'application/vnd.oasis.opendocument.text',
		},
		spreadsheet: {
			extension: 'ods',
			mime: 'application/vnd.oasis.opendocument.spreadsheet',
		},
		presentation: {
			extension: 'odp',
			mime: 'application/vnd.oasis.opendocument.presentation',
		},
	}
}

const getFileType = (document) => {
	return getFileTypes()[document]
}

export default {
	getFileTypes,
	getFileType,
}
