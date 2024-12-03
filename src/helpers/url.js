/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getRootUrl } from '@nextcloud/router'
import { languageToBCP47 } from './index'
import Config from './../services/config'

const getSearchParam = (name) => {
	const results = new RegExp('[?&]' + name + '=([^&#]*)').exec(window.location.href)
	if (results === null) {
		return null
	}
	return decodeURI(results[1]) || 0
}

const getWopiUrl = ({ fileId, title, readOnly, closeButton, revisionHistory }) => {
	// WOPISrc - URL that loolwsd will access (ie. pointing to ownCloud)
	// index.php is forced here to avoid different wopi srcs for the same document
	const wopiurl = window.location.protocol + '//' + window.location.host + getRootUrl() + '/index.php/apps/officeonline/wopi/files/' + fileId
	console.debug('[getWopiUrl] ' + wopiurl)
	const wopisrc = encodeURIComponent(wopiurl)

	// urlsrc - the URL from discovery xml that we access for the particular
	// document; we add various parameters to that.
	// The discovery is available at
	//   https://<loolwsd-server>:9980/hosting/discovery
	return Config.get('urlsrc')
		+ 'WOPISrc=' + wopisrc
		+ '&title=' + encodeURIComponent(title)
		+ '&lang=' + languageToBCP47()
		+ (closeButton ? '&closebutton=1' : '')
		+ (revisionHistory ? '&revisionhistory=1' : '')
		+ (readOnly ? '&permission=readonly' : '')
}

const getDocumentUrlFromTemplate = (templateId, fileName, fileDir, fillWithTemplate) => {
	return OC.generateUrl(
		'apps/officeonline/indexTemplate?templateId={templateId}&fileName={fileName}&dir={dir}&requesttoken={requesttoken}',
		{
			templateId: templateId,
			fileName: fileName,
			dir: encodeURIComponent(fileDir),
			requesttoken: OC.requestToken,
		}
	)
}

const getDocumentUrlForPublicFile = (fileName, fileId) => {
	return OC.generateUrl(
		'apps/officeonline/public?shareToken={shareToken}&fileName={fileName}&requesttoken={requesttoken}&fileId={fileId}',
		{
			shareToken: document.getElementById('sharingToken').value,
			fileName: encodeURIComponent(fileName),
			fileId: fileId,
			requesttoken: OC.requestToken,
		}
	)
}

const getDocumentUrlForFile = (fileDir, fileId) => {
	return OC.generateUrl(
		'apps/officeonline/index?fileId={fileId}&requesttoken={requesttoken}',
		{
			fileId: fileId,
			dir: fileDir,
			requesttoken: OC.requestToken,
		})
}

export {
	getSearchParam,
	getWopiUrl,

	getDocumentUrlFromTemplate,
	getDocumentUrlForPublicFile,
	getDocumentUrlForFile,
}
