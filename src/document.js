/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getRootUrl } from '@nextcloud/router'
import { getRequestToken } from '@nextcloud/auth'
import Config from './services/config.ts'
import { setGuestNameCookie, shouldAskForGuestName } from './helpers/guestName'
import PostMessageService from './services/postMessage.ts'
import {
	callMobileMessage,
	isDirectEditing,
	isMobileInterfaceAvailable,
} from './helpers/mobile'
import { getWopiUrl, getSearchParam } from './helpers/url'

import '../css/document.scss'

// FIXME: Remove dependency in a next step
const $ = window.$

const PostMessages = new PostMessageService({
	parent: window.parent,
	loolframe: () => document.getElementById('loleafletframe').contentWindow,
})

let checkingProxyStatus = false

const checkProxyStatus = () => {
	checkingProxyStatus = true
	const url = Config.get('urlsrc').slice(0, Config.get('urlsrc').indexOf('proxy.php') + 'proxy.php'.length)
	$.get(url + '?status').done(function(val) {
		if (val && val.status && val.status !== 'OK') {
			if (val.status === 'starting' || val.status === 'stopped') {
				document.getElementById('proxyLoadingIcon').classList.add('icon-loading-small')
				document.getElementById('proxyLoadingMessage').textContent = t('officeonline', 'Built-in CODE Server is starting up shortly, please wait.')
			} else if (val.status === 'restarting') {
				document.getElementById('proxyLoadingIcon').classList.add('icon-loading-small')
				document.getElementById('proxyLoadingMessage').textContent = t('officeonline', 'Built-in CODE Server is restarting, please wait.')
			} else if (val.status === 'error') {
				if (val.error === 'appimage_missing') {
					document.getElementById('proxyLoadingMessage').textContent = t('officeonline', 'Error: Cannot find the AppImage, please reinstall the Collabora Online Built-in server.')
				} else if (val.error === 'chmod_failed' || val.error === 'appimage_not_executable') {
					document.getElementById('proxyLoadingMessage').textContent = t('officeonline', 'Error: Unable to make the AppImage executable, please setup a standalone server.')
				} else if (val.error === 'exec_disabled') {
					document.getElementById('proxyLoadingMessage').textContent = t('officeonline', 'Error: Exec disabled in PHP, please enable it, or setup a standalone server.')
				} else if (val.error === 'not_linux' || val.error === 'not_x86_64') {
					document.getElementById('proxyLoadingMessage').textContent = t('officeonline', 'Error: Not running on x86-64 Linux, please setup a standalone server.')
				} else if (val.error === 'no_fontconfig') {
					document.getElementById('proxyLoadingMessage').textContent = t('officeonline', 'Error: The fontconfig library is not installed on your server, please install it or setup a standalone server.')
				} else if (val.error === 'no_glibc') {
					document.getElementById('proxyLoadingMessage').textContent = t('officeonline', 'Error: Not running on glibc-based Linux, please setup a standalone server.')
				} else {
					document.getElementById('proxyLoadingMessage').textContent = t('officeonline', 'Error: Cannot start the Collabora Online Built-in server, please setup a standalone one.')
				}

				// probably not even worth re-trying
				return
			}

			// retry...
			setTimeout(function() { checkProxyStatus() }, 100)
			return
		}

		checkingProxyStatus = false
	})
}

const showLoadingIndicator = () => {
	if (OC.appswebroots.officeonlinecode && Config.get('urlsrc').indexOf('proxy.php') >= 0) {
		checkProxyStatus()
	} else {
		document.getElementById('loadingContainer').classList.add('icon-loading')
	}
}

const hideLoadingIndicator = () => {
	if (checkingProxyStatus) {
		setTimeout(function() { hideLoadingIndicator() }, 100)
		return
	}

	document.getElementById('loadingContainer').classList.remove('icon-loading')
	document.getElementById('proxyLoadingIcon').classList.remove('icon-loading-small')
	document.getElementById('proxyLoadingMessage').textContent = ''
}

showLoadingIndicator()

$.widget('oc.guestNamePicker', {
	_create: function() {
		hideLoadingIndicator()

		const text = document.createElement('div')
		text.setAttribute('style', 'margin: 0 auto; margin-top: 100px; text-align: center;')
		text.innerHTML = t('officeonline', 'Please choose your nickname to continue as guest user.')

		const div = document.createElement('div')
		div.setAttribute('style', 'margin: 0 auto; width: 250px; display: flex;')
		const nick = '<input type="text" placeholder="' + t('officeonline', 'Nickname') + '" id="nickname" style="flex-grow: 1; border-right:none; border-top-right-radius: 0; border-bottom-right-radius: 0">'
		const btn = '<input style="border-left:none; border-top-left-radius: 0; border-bottom-left-radius: 0; margin-left: -3px" type="button" id="btn" type="button" value="' + t('officeonline', 'Set') + '">'
		div.innerHTML = nick + btn

		$('#documents-content').prepend(div)
		$('#documents-content').prepend(text)
		const setGuestNameSubmit = () => {
			const username = $('#nickname').val()
			setGuestNameCookie(username)
			window.location.reload(true)
		}

		$('#nickname').keyup(function(event) {
			if (event.which === 13) {
				setGuestNameSubmit()
			}
		})
		$('#btn').click(() => setGuestNameSubmit())
	},
})

/**
 * Type definitions for WOPI Post message objects
 *
 * @typedef {Object} View
 * @property {Number} ViewId
 * @property {string} UserName
 * @property {string} UserId
 * @property {Number} Color
 * @property {Boolean} ReadOnly
 * @property {Boolean} IsCurrentView
 */

const documentsMain = {
	isEditorMode: false,
	isViewerMode: false,
	isFrameReady: false,
	ready: false,
	fileName: null,
	baseName: null,
	canShare: false,
	canEdit: false,
	renderComplete: false, // false till page is rendered with all required data about the document(s)
	$deferredVersionRestoreAck: null,
	wopiClientFeatures: null,

	// generates docKey for given fileId
	_generateDocKey: function(wopiFileId) {
		let canonicalWebroot = Config.get('canonical_webroot')
		let ocurl = getRootUrl() + '/index.php/apps/officeonline/wopi/files/' + wopiFileId
		if (canonicalWebroot) {
			if (!canonicalWebroot.startsWith('/')) {
				canonicalWebroot = '/' + canonicalWebroot
			}
			Config.update('canonical_webroot', canonicalWebroot)
			ocurl = ocurl.replace(getRootUrl(), canonicalWebroot)
		}

		return ocurl
	},

	UI: {
		/* Editor wrapper HTML */
		container: '<div id="mainContainer" class="claro">'
					+ '</div>',

		viewContainer: '<div id="revViewerContainer" class="claro">'
						+ '<div id="revViewer"></div>'
						+ '</div>',

		showViewer: function(fileId, title) {
			// remove previous viewer, if open, and set a new one
			if (documentsMain.isViewerMode) {
				$('#revViewer').remove()
				$('#revViewerContainer').prepend($('<div id="revViewer">'))
			}

			const urlsrc = getWopiUrl({ fileId, title, readOnly: true })

			// access_token - must be passed via a form post
			const accessToken = encodeURIComponent(documentsMain.token)

			// form to post the access token for WOPISrc
			const form = '<form id="loleafletform_viewer" name="loleafletform_viewer" target="loleafletframe_viewer" action="' + urlsrc + '" method="post">'
				+ '<input name="access_token" value="' + accessToken + '" type="hidden"/></form>'

			// iframe that contains the Collabora Online Viewer
			const frame = '<iframe id="loleafletframe_viewer" name="loleafletframe_viewer" nonce="' + btoa(getRequestToken()) + '" style="width:100%;height:100%;position:absolute;"/>'

			$('#revViewer').append(form)
			$('#revViewer').append(frame)

			// submit that
			$('#loleafletform_viewer').submit()
			documentsMain.isViewerMode = true
			// for closing revision mode
			$('#revViewerContainer .closeButton').click(function(e) {
				e.preventDefault()
				documentsMain.onCloseViewer()
			})
		},

		loadRevViewerContainer: function() {
			if (!$('revViewerContainer').length) {
				$(document.body).prepend(documentsMain.UI.viewContainer)
				const closeButton = $('<button class="icon-close closeButton" title="' + t('officeonline', 'Close version preview') + '"/>')
				$('#revViewerContainer').prepend(closeButton)
			}
		},

		showEditor: function(title, fileId, action) {
			if (!documentsMain.renderComplete) {
				setTimeout(function() { documentsMain.UI.showEditor(title, fileId, action) }, 10)
				console.debug('Waiting for page to render…')
				return
			}

			OC.Util.History.addOnPopStateHandler(window._.bind(documentsMain.onClose))
			OC.Util.History.pushState()

			PostMessages.sendPostMessage('parent', 'loading')
			hideLoadingIndicator()

			$(document.body).addClass('claro')
			$(document.body).prepend(documentsMain.UI.container)

			const urlsrc = getWopiUrl({ fileId, title, readOnly: false, closeButton: true, revisionHistory: true })

			// access_token - must be passed via a form post
			const accessToken = encodeURIComponent(documentsMain.token)

			// form to post the access token for WOPISrc
			const form = '<form id="loleafletform" name="loleafletform" target="loleafletframe" action="' + urlsrc + '" method="post">'
				+ '<input name="access_token" value="' + accessToken + '" type="hidden"/></form>'

			// iframe that contains the Collabora Online
			const frame = '<iframe id="loleafletframe" name="loleafletframe" nonce="' + btoa(getRequestToken()) + '" scrolling="no" allowfullscreen style="width:100%;height:100%;position:absolute;" />'

			$('#mainContainer').append(form)
			$('#mainContainer').append(frame)

			// Listen for App_LoadingStatus as soon as possible
			$('#loleafletframe').ready(function() {
				const editorInitListener = ({ parsed, data }) => {
					console.debug('[document] editorInitListener: Received post message ', parsed)
					const { msgId, args } = parsed

					if (msgId !== 'App_LoadingStatus') {
						return
					}

					// Pass though all messages to viewer.js if not direct editing
					if (!isDirectEditing()) {
						PostMessages.sendPostMessage('parent', data)
					}

					switch (args.Status) {
					case 'Frame_Ready':
						documentsMain.isFrameReady = true
						documentsMain.wopiClientFeatures = args.Features
						callMobileMessage('documentLoaded')
						break
					case 'Document_Loaded':
						PostMessages.unregisterPostMessageHandler(editorInitListener)

						// Hide buttons when using the mobile app integration
						if (isDirectEditing()) {
							PostMessages.sendWOPIPostMessage('loolframe', 'Hide_Button', { id: 'fullscreen' })
							PostMessages.sendWOPIPostMessage('loolframe', 'Hide_Menu_Item', { id: 'fullscreen' })
						}
						break
					case 'Failed':
						// Loading failed but editor shows the error
						documentsMain.isFrameReady = true
						break
					}
				}

				PostMessages.registerPostMessageHandler(editorInitListener)

				// In case of editor inactivity
				setTimeout(function() {
					if (!documentsMain.isFrameReady) {
						const message = { 'MessageId': 'App_LoadingStatus', 'Values': { 'Status': 'Timeout' } }
						editorInitListener({ data: JSON.stringify(message), parsed: message })
					}
				}, 45000)
			})

			$('#loleafletframe').on('load', function() {
				const ViewerToLool = [
					'Action_FollowUser',
					'Host_VersionRestore',
					'Action_RemoveView',
				]
				PostMessages.registerPostMessageHandler(({ parsed, data }) => {
					console.debug('[document] Received post message ', parsed)
					const { msgId, args, deprecated } = parsed

					if (deprecated) {
						return
					}

					if (documentsMain.isViewerMode) {
						let { fileId, title } = args
						const { version } = args
						switch (parsed.msgId) {
						case 'Action_loadRevViewer':
							documentsMain.UI.loadRevViewerContainer()
							if (fileId) {
								fileId += '_' + Config.get('instanceId')
								if (version) {
									fileId += `_${version}`
									title += `_${version}`
								}
								documentsMain.UI.showViewer(
									fileId, title
								)
							}
							break
						case 'Host_VersionRestore':
							// resolve the deferred object immediately if client doesn't support version states
							if (!documentsMain.wopiClientFeatures || !documentsMain.wopiClientFeatures.VersionStates) {
								console.error('No version support')
								// Not forwarding message to collabora
								return
							}
							documentsMain.onCloseViewer()
							break
						case 'App_VersionRestore':
							// Status = Pre_Restore_Ack -> Ready to restore version
							break
						default:
							return
						}

					}

					// Pass all messages to viewer if not direct editing or
					if (!isDirectEditing() && ViewerToLool.indexOf(msgId) === -1) {
						PostMessages.sendPostMessage('parent', data)
					}
					// Pass messages from viewer to lool
					if (ViewerToLool.indexOf(msgId) >= 0) {
						return PostMessages.sendPostMessage('loolframe', data)
					}

					if (isMobileInterfaceAvailable()) {
						if (msgId === 'Download_As') {
							return callMobileMessage('downloadAs', args)
						}
						if (msgId === 'File_Rename') {
							return callMobileMessage('fileRename', args)
						} else if (msgId === 'UI_Paste') {
							documentsMain.callMobileMessage('paste')
							return
						}
						if (msgId === 'UI_Close') {
							callMobileMessage('close')
						} else if (msgId === 'UI_Share') {
							callMobileMessage('share')
						}
						// Fallback to web UI for SaveAs, otherwise ignore other post messages
						if (msgId !== 'UI_SaveAs') {
							return
						}
					}

					switch (parsed.msgId) {
					case 'UI_Close':
					case 'close':
						documentsMain.onClose()
						break
						// Messages received from the viewer
					case 'postAsset':
						documentsMain.postAsset(args.FileName, args.Url)
						break
					case 'UI_FileVersions':
					case 'rev-history':
						documentsMain.UI.loadRevViewerContainer()
						documentsMain.UI.showViewer(
							documentsMain.fileId, documentsMain.title
						)
						break
					case 'RD_Version_Restored':
						$('#loleafletform_viewer').submit()
						break
					default:
						console.debug('[document] Unhandled post message', parsed)
					}

					if (msgId === 'UI_SaveAs') {
						// TODO Move to file picker dialog with input field
						OC.dialogs.prompt(
							t('officeonline', 'Please enter the filename to store the document as.'),
							t('officeonline', 'Save As'),
							function(result, value) {
								if (result === true && value) {
									PostMessages.sendWOPIPostMessage('loolframe', 'Action_SaveAs', { 'Filename': value })
								}
							},
							true,
							t('officeonline', 'New filename'),
							false
						).then(function() {
							const $dialog = $('.oc-dialog:visible')
							const $buttons = $dialog.find('button')
							$buttons.eq(0).text(t('officeonline', 'Cancel'))
							$buttons.eq(1).text(t('officeonline', 'Save'))
							const nameInput = $dialog.find('input')[0]
							nameInput.style.minWidth = '250px'
							nameInput.style.maxWidth = '400px'
							nameInput.value = documentsMain.fileName
							nameInput.selectionStart = 0
							nameInput.selectionEnd = documentsMain.fileName.lastIndexOf('.')
						})
					}
				})

				// Tell the LOOL iframe that we are ready now
				PostMessages.sendWOPIPostMessage('loolframe', 'Host_PostmessageReady', {})
			})

			// submit that
			$('#loleafletform').submit()
		},

		hideEditor: function() {
			// Fade out editor
			$('#mainContainer').fadeOut('fast', function() {
				$('#mainContainer').remove()
				$('#content-wrapper').fadeIn('fast')
				$(document.body).removeClass('claro')
			})
		},
	},

	onStartup: function() {
		// Does anything indicate that we need to autostart a session?
		const fileId = (getSearchParam('fileId') || '').replace(/^\W*/, '')

		if (fileId && Number.isInteger(Number(fileId)) && $('#nickname').length === 0) {
			documentsMain.isEditorMode = true
			documentsMain.originalFileId = fileId
		}

		documentsMain.ready = true
	},

	initSession: function() {
		documentsMain.urlsrc = Config.get('urlsrc')
		documentsMain.fullPath = Config.get('path')
		documentsMain.token = Config.get('token')
		documentsMain.fileId = Config.get('fileId')
		documentsMain.fileName = Config.get('title')
		documentsMain.canEdit = Boolean(Config.get('permissions') & OC.PERMISSION_UPDATE)
		documentsMain.canShare = typeof OC.Share !== 'undefined' && Config.get('permissions') & OC.PERMISSION_SHARE

		$('footer,nav').hide()
		// fade out file list and show the document
		$('#content-wrapper').fadeOut('fast').promise().done(function() {
			documentsMain.loadDocument(documentsMain.fileName, documentsMain.fileId)
		})
	},

	loadDocument: function(title, fileId) {
		documentsMain.UI.showEditor(title, fileId, 'write')
	},

	onEditorShutdown: function(message) {
		OC.Notification.show(message)

		$(window).off('beforeunload')
		$(window).off('unload')
		if (documentsMain.isEditorMode) {
			documentsMain.isEditorMode = false
		} else {
			setTimeout(OC.Notification.hide, 7000)
		}
		documentsMain.UI.hideEditor()

		$('footer,nav').show()
	},

	onClose: function() {
		documentsMain.isEditorMode = false
		$(window).off('beforeunload')
		$(window).off('unload')

		$('footer,nav').show()
		documentsMain.UI.hideEditor()

		PostMessages.sendPostMessage('parent', 'close', '*')
	},

	onCloseViewer: function() {
		$('#revisionsContainer *').off()

		$('#revPanelContainer').remove()
		$('#revViewerContainer').remove()
		documentsMain.isViewerMode = false

		$('#loleafletframe').focus()
	},

	postAsset: function(filename, url) {
		PostMessages.sendWOPIPostMessage('loolframe', 'Action_InsertGraphic', {
			filename: filename,
			url: url,
		})
	},

	postGrabFocus: function() {
		PostMessages.sendWOPIPostMessage('loolframe', 'Grab_Focus')
	},
}

$(document).ready(function() {

	if (!OCA.OfficeOnline) {
		OCA.OfficeOnline = {}
	}

	if (!OC.Share) {
		OC.Share = {}
	}

	OCA.OfficeOnline.documentsMain = documentsMain

	if (shouldAskForGuestName()) {
		PostMessages.sendPostMessage('parent', 'loading')
		$('#documents-content').guestNamePicker()
	} else {
		documentsMain.initSession()
	}
	documentsMain.renderComplete = true

	const viewport = document.querySelector('meta[name=viewport]')
	viewport.setAttribute('content', 'width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no')

	documentsMain.onStartup()

	window.documentsMain = documentsMain
})
