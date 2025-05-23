<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<transition name="fade" appear>
		<div v-show="loading" id="officeonline-wrapper">
			<iframe id="officeonlineframe" ref="documentFrame" :src="src" />
		</div>
	</transition>
</template>

<script>
import { getSharingToken } from '@nextcloud/sharing/public'
import { getCurrentDirectory } from './../helpers/index.js'

import { getDocumentUrlForFile, getDocumentUrlForPublicFile } from '../helpers/url.js'
import PostMessageService from '../services/postMessage.ts'

const PostMessages = new PostMessageService({
	FRAME_DOCUMENT: () => document.getElementById('officeonlineframe').contentWindow,
})

export default {
	name: 'Office',
	props: {
		filename: {
			type: String,
			default: null,
		},
		fileid: {
			type: Number,
			default: null,
		},
		hasPreview: {
			type: Boolean,
			required: false,
			default: () => false,
		},
	},
	data() {
		return {
			src: null,
			loading: false,
		}
	},
	computed: {
		viewColor() {
			return view => ({
				'border-color': '#' + ('000000' + Number(view.Color).toString(16)).slice(-6),
				'border-width': '2px',
				'border-style': 'solid',
			})
		},
	},
	mounted() {
		PostMessages.registerPostMessageHandler(({ parsed }) => {
			const { msgId, args, deprecated } = parsed
			console.debug('[viewer] Received post message', parsed, { msgId, args, deprecated })
			if (deprecated) { return }

			switch (msgId) {
			case 'loading':
				break
			case 'close':
				this.$parent.close && this.$parent.close()
				break
			}
		})
		this.load()
	},
	methods: {
		async load() {
			const sharingToken = getSharingToken()
			let documentUrl = ''
			if (sharingToken) {
				documentUrl = getDocumentUrlForPublicFile(this.filename, this.fileid) + '&path=' + encodeURIComponent(this.filename)
			} else {
				documentUrl = getDocumentUrlForFile(this.filename, this.fileid) + '&path=' + encodeURIComponent(this.filename)
			}
			this.$emit('update:loaded', true)
			this.src = documentUrl
			this.loading = true
		},
	},
}
</script>
<style lang="scss">
	#officeonline-wrapper {
		width: 100vw;
		height: calc(100vh - 50px);
		left: 0;
		top: 0;
		position: absolute;
		z-index: 100000;
		max-width: 100%;
		display: flex;
		flex-direction: column;
		background-color: var(--color-main-background);
		transition: opacity .25s;
	}

	iframe {
		width: 100%;
		flex-grow: 1;
	}

	.fade-enter-active, .fade-leave-active {
		transition: opacity .25s;
	}

	.fade-enter, .fade-leave-to {
		opacity: 0;
	}
</style>
