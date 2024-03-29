<!--
  - @copyright Copyright (c) 2019 Julius Härtl <jus@bitgrid.net>
  -
  - @author Julius Härtl <jus@bitgrid.net>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<transition name="fade" appear>
		<div v-show="loading" id="officeonline-wrapper">
			<iframe id="officeonlineframe" ref="documentFrame" :src="src" />
		</div>
	</transition>
</template>

<script>
import { getCurrentDirectory } from './../helpers/index.js'

import { getDocumentUrlForFile, getDocumentUrlForPublicFile } from '../helpers/url'
import PostMessageService from '../services/postMessage'

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
			const sharingToken = document.getElementById('sharingToken')
			const dir = getCurrentDirectory()
			let documentUrl = ''
			if (sharingToken && dir === '') {
				documentUrl = getDocumentUrlForPublicFile(this.filename)
			} else if (sharingToken) {
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
