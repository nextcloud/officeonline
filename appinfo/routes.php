<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-FileCopyrightText: 2013-2014 Victor Dubiniuk <victor.dubiniuk@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Officeonline\AppInfo;

return [
	'routes' => [
		//documents
		['name' => 'document#index', 'url' => 'index', 'verb' => 'GET'],
		['name' => 'document#remote', 'url' => 'remote', 'verb' => 'GET'],
		['name' => 'document#open', 'url' => 'open', 'verb' => 'GET'],

		['name' => 'document#createFromTemplate', 'url' => 'indexTemplate', 'verb' => 'GET'],
		['name' => 'document#publicPage', 'url' => '/public', 'verb' => 'GET'],
		['name' => 'document#create', 'url' => 'ajax/documents/create', 'verb' => 'POST'],

		// external api access
		['name' => 'document#extAppGetData', 'url' => '/ajax/extapp/data/{fileId}', 'verb' => 'POST'],

		// WOPI access
		['name' => 'wopi#checkFileInfo', 'url' => 'wopi/files/{fileId}', 'verb' => 'GET'],
		['name' => 'wopi#getFile', 'url' => 'wopi/files/{fileId}/contents', 'verb' => 'GET'],
		['name' => 'wopi#putFile', 'url' => 'wopi/files/{fileId}/contents', 'verb' => 'POST'],
		['name' => 'wopi#postFile', 'url' => 'wopi/files/{fileId}', 'verb' => 'POST'],
		['name' => 'wopi#getTemplate', 'url' => 'wopi/template/{fileId}', 'verb' => 'GET'],

		//settings
		['name' => 'settings#setPersonalSettings', 'url' => 'ajax/personal.php', 'verb' => 'POST'],
		['name' => 'settings#setSettings', 'url' => 'ajax/admin.php', 'verb' => 'POST'],
		['name' => 'settings#getSettings', 'url' => 'ajax/settings.php', 'verb' => 'GET'],
		['name' => 'settings#checkSettings', 'url' => 'settings/check', 'verb' => 'GET'],

		// templates
		['name' => 'templates#getPreview', 'url' => '/template/preview/{fileId}', 'verb' => 'GET'],
		['name' => 'templates#add', 'url' => '/template', 'verb' => 'POST'],
		['name' => 'templates#delete', 'url' => '/template/{fileId}', 'verb' => 'DELETE'],
	],
	'ocs' => [
		['name' => 'OCS#create', 'url' => '/api/v1/document', 'verb' => 'POST'],
		['name' => 'OCS#getTemplates', 'url' => '/api/v1/templates/{type}', 'verb' => 'GET'],
		['name' => 'OCS#createFromTemplate', 'url' => '/api/v1/templates/new', 'verb' => 'POST'],

		['name' => 'Federation#index', 'url' => '/api/v1/federation', 'verb' => 'GET'],
		['name' => 'Federation#remoteWopiToken', 'url' => '/api/v1/federation', 'verb' => 'POST'],
		['name' => 'Federation#remoteDirectToken', 'url' => '/api/v1/federation/direct', 'verb' => 'POST'],

	],
];
