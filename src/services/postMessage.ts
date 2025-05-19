/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
type MessageEventSource = Window | MessagePort | ServiceWorker;

export interface WopiPost {
	MessageId: string;
	Values: WopiPostValues;
}

export interface WopiPostValues {
	Deprecated?: boolean;
}

interface WindowCallbackHandler { (): Window}

export default class PostMessageService {
	protected targets: {[name: string]: (Window|WindowCallbackHandler)};
	protected postMessageHandlers: Array<(data: { data: any, parsed: { msgId: string, args: WopiPostValues, deprecated: boolean } }) => void>;

	constructor(targets: {[name: string]: (Window|WindowCallbackHandler)}) {
		this.targets = targets;
		this.postMessageHandlers = [];
		window.addEventListener('message', (event: MessageEvent) => {
			this.handlePostMessage(event.data)
		}, false)
	}

	sendPostMessage(target: string, message: any, targetOrigin: string = '*') {
		let targetElement: Window;
		if (typeof this.targets[target] === 'function') {
			targetElement = (this.targets[target] as WindowCallbackHandler)()
		} else {
			targetElement = this.targets[target] as Window
		}
		targetElement.postMessage(message, targetOrigin)
		console.debug('PostMessageService.sendPostMessage', target, message)
	}

	sendWOPIPostMessage(target: string, msgId: string, values: any = {}) {
		const msg = {
			MessageId: msgId,
			SendTime: Date.now(),
			Values: values
		}

		this.sendPostMessage(target, JSON.stringify(msg))
	}

	private static parsePostMessage(data: any) {
		let msgId: string = '';
		let args: WopiPostValues = {};
		let deprecated: boolean = false;

		try {
			const msg: WopiPost = JSON.parse(data)
			msgId = msg.MessageId
			args = msg.Values
			deprecated = !!msg.Values.Deprecated
		} catch (exc) {
			msgId = data
		}
		return { msgId, args, deprecated }
	}

	registerPostMessageHandler(callback: (data: { data: any, parsed: { msgId: string, args: WopiPostValues, deprecated: boolean } }) => void) {
		this.postMessageHandlers.push(callback)
	}

	unregisterPostMessageHandler(callback: (data: { data: any, parsed: { msgId: string, args: WopiPostValues, deprecated: boolean } }) => void) {
		const handlerIndex = this.postMessageHandlers.findIndex(cb => cb === callback)
		delete this.postMessageHandlers[handlerIndex]
	}

	private handlePostMessage(data: any) {
		const parsed = PostMessageService.parsePostMessage(data);
		if (typeof parsed === 'undefined' || parsed === null) {
			return
		}
		this.postMessageHandlers.forEach((fn: Function): void => {
			if (parsed.deprecated) {
				console.debug('PostMessageService.handlePostMessage', 'Ignoring deprecated post message', parsed.msgId)
				return;
			}
			fn({
				data: data,
				parsed
			})
		})
	}
}
