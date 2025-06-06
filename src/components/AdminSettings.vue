<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<div class="section">
			<h2>Office Online</h2>

			<div v-if="settings.wopi_url && settings.wopi_url !== ''">
				<div v-if="serverError == 2" id="security-warning-state-failure">
					<span class="icon icon-close-white" /><span class="message">{{ t('officeonline', 'Could not establish connection to the Office Online server.') }}</span>
				</div>
				<div v-else-if="serverError == 1" id="security-warning-state-failure">
					<span class="icon icon-loading" /><span class="message">{{ t('officeonline', 'Setting up a new server') }}</span>
				</div>
				<div v-else id="security-warning-state-ok">
					<span class="icon icon-checkmark-white" /><span class="message">{{ t('officeonline', 'Office Online server is reachable.') }}</span>
				</div>
			</div>
			<div v-else id="security-warning-state-warning">
				<span class="icon icon-error-white" /><span class="message">{{ t('officeonline', 'Please configure a Office Online server to start editing documents') }}</span>
			</div>

			<fieldset>
				<form @submit.prevent.stop="updateServer">
					<p>
						<label for="wopi_url">{{ t('officeonline', 'URL (and Port) of Office Online server') }}</label><br>
						<input id="wopi_url"
							v-model="settings.wopi_url"
							type="text"
							:disabled="updating">
						<input type="submit" value="Save" :disabled="updating"><br>
					</p>
					<p>
						<input id="disable_certificate_verification"
							v-model="settings.disable_certificate_verification"
							type="checkbox"
							class="checkbox"
							:disabled="updating"
							@change="updateServer">
						<label for="disable_certificate_verification">{{ t('officeonline', 'Disable certificate verification (insecure)') }}</label><br>
						<em>{{ t('Enable if your Office Online server uses a self signed certificate') }}</em>
					</p>
				</form>
			</fieldset>
		</div>

		<div v-if="isSetup" id="advanced-settings" class="section">
			<h2>{{ t('officeonline', 'Advanced settings') }}</h2>
			<SettingsCheckbox :value="isOoxml"
				:label="t('officeonline', 'Use Office Open XML (OOXML) instead of OpenDocument Format (ODF) by default for new files')"
				hint=""
				:disabled="updating"
				@input="updateOoxml" />

			<SettingsCheckbox :value="settings.use_groups !== null"
				:label="t('officeonline', 'Restrict usage to specific groups')"
				:hint="t('officeonline', 'Office Online is enabled for all users by default. When this setting is active, only members of the specified groups can use it.')"
				:disabled="updating"
				@input="updateUseGroups">
				<SettingsSelectGroup v-if="settings.use_groups !== null"
					v-model="settings.use_groups"
					:label="t('officeonline', 'Select groups')"
					class="option-inline"
					:disabled="updating"
					@input="updateUseGroups" />
			</SettingsCheckbox>

			<SettingsCheckbox :value="settings.edit_groups !== null"
				:label="t('officeonline', 'Restrict edit to specific groups')"
				hint="All users can edit documents with Office Online by default. When this setting is active, only the members of the specified groups can edit and the others can only view documents.')"
				:disabled="updating"
				@input="updateEditGroups">
				<SettingsSelectGroup v-if="settings.edit_groups !== null"
					v-model="settings.edit_groups"
					:label="t('officeonline', 'Select groups')"
					class="option-inline"
					:disabled="updating"
					@input="updateEditGroups" />
			</SettingsCheckbox>
		</div>
	</div>
</template>

<script>
import Vue from 'vue'
import axios from '@nextcloud/axios'
import SettingsCheckbox from './SettingsCheckbox'
import SettingsSelectGroup from './SettingsSelectGroup'
import { generateUrl } from '@nextcloud/router'
import { getCapabilities } from '@nextcloud/capabilities'

const SERVER_STATE_OK = 0
const SERVER_STATE_LOADING = 1
const SERVER_STATE_CONNECTION_ERROR = 2

export default {
	name: 'AdminSettings',
	components: {
		SettingsCheckbox,
		SettingsSelectGroup,
	},
	props: {
		initial: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			serverMode: '',
			serverError: Object.values(getCapabilities().officeonline.discovery).length > 0 ? SERVER_STATE_OK : SERVER_STATE_CONNECTION_ERROR,
			updating: false,
			groups: [],
			tags: [],
			uiVisible: {
				canonical_webroot: false,
				external_apps: false,
			},
			settings: {
				demoUrl: null,
				wopi_url: null,
				watermark: {
					enabled: false,
					shareAll: false,
					shareRead: false,
					linkSecure: false,
					linkRead: false,
					linkAll: false,
					linkTags: false,
					linkTagsList: [],
					allGroups: false,
					allGroupsList: [],
					allTags: false,
					allTagsList: [],
					text: '',
				},
			},
		}
	},
	computed: {
		providerDescription() {
			return t('officeonline', 'Contact {0} to get an own installation.', [this.settings.demoUrl.provider_name])
		},
		isSetup() {
			return this.serverError === SERVER_STATE_OK
		},
		isOoxml() {
			return this.settings.doc_format === 'ooxml'
		},
		hasHostErrors() {
			return this.hostErrors.some(x => x)
		},
	},
	beforeMount() {
		for (const key in this.initial.settings) {
			if (!Object.prototype.hasOwnProperty.call(this.initial.settings, key)) {
				continue
			}

			const [parent, setting] = key.split('_')
			if (parent === 'watermark') {
				Vue.set(this.settings[parent], setting, this.initial.settings[key])
			} else {
				Vue.set(this.settings, key, this.initial.settings[key])
			}

		}
		Vue.set(this.settings, 'data', this.initial.settings)
		if (this.settings.wopi_url === '') {
			this.serverError = SERVER_STATE_CONNECTION_ERROR
		}
		Vue.set(this.settings, 'edit_groups', this.settings.edit_groups ? this.settings.edit_groups.split('|') : null)
		Vue.set(this.settings, 'use_groups', this.settings.use_groups ? this.settings.use_groups.split('|') : null)

		this.uiVisible.canonical_webroot = !!(this.settings.canonical_webroot && this.settings.canonical_webroot !== '')
		this.uiVisible.external_apps = !!(this.settings.external_apps && this.settings.external_apps !== '')

		this.demoServers = this.initial.demo_servers

		if (this.initial.web_server && this.initial.web_server.length > 0) {
			this.isNginx = this.initial.web_server.indexOf('nginx') !== -1
		}
		if (this.initial.os_family && this.initial.os_family.length > 0) {
			this.CODECompatible = this.CODECompatible && this.initial.os_family === 'Linux'
		}
		if (this.initial.platform && this.initial.platform.length > 0) {
			this.CODECompatible = this.CODECompatible && this.initial.platform === 'x86_64'
		}
		this.checkIfDemoServerIsActive()
	},
	methods: {
		async fetchDemoServers() {
			try {
				const result = await axios.get(generateUrl('/apps/officeonline/settings/demo'))
				this.demoServers = result.data
			} catch (e) {
				this.demoServers = []
			}
		},
		update() {
			this.updating = true
			const settings = this.settings
			axios.post(generateUrl('/apps/officeonline/settings/watermark'), { settings }).then((response) => {
				this.updating = false
			}).catch((error) => {
				this.updating = false
				OC.Notification.showTemporary(t('officeonline', 'Failed to save settings'))
				console.error(error)
			})
		},
		async updateUseGroups(enabled) {
			if (enabled) {
				this.settings.use_groups = enabled === true ? [] : enabled
			} else {
				this.settings.use_groups = null
			}
			await this.updateSettings({
				use_groups: this.settings.use_groups !== null ? this.settings.use_groups.join('|') : '',
			})
		},
		async updateEditGroups(enabled) {
			if (enabled) {
				this.settings.edit_groups = enabled === true ? [] : enabled
			} else {
				this.settings.edit_groups = null
			}
			await this.updateSettings({
				edit_groups: this.settings.edit_groups !== null ? this.settings.edit_groups.join('|') : '',
			})
		},
		async updateCanonicalWebroot(canonicalWebroot) {
			this.settings.canonical_webroot = (typeof canonicalWebroot === 'boolean') ? '' : canonicalWebroot
			if (canonicalWebroot === true) {
				return
			}
			await this.updateSettings({
				canonical_webroot: this.settings.canonical_webroot,
			})
		},
		async updateExternalApps(externalApps) {
			this.settings.external_apps = (typeof externalApps === 'boolean') ? '' : externalApps
			if (externalApps === true) {
				return
			}
			await this.updateSettings({
				external_apps: this.settings.external_apps,
			})
		},
		async updateOoxml(enabled) {
			this.settings.doc_format = enabled ? 'ooxml' : ''
			await this.updateSettings({
				doc_format: this.settings.doc_format,
			})
		},
		async updateServer() {
			this.serverError = SERVER_STATE_LOADING
			try {
				await this.updateSettings({
					wopi_url: this.settings.wopi_url,
					disable_certificate_verification: this.settings.disable_certificate_verification,
				})
				this.serverError = SERVER_STATE_OK
			} catch (e) {
				console.error(e)
				this.serverError = SERVER_STATE_CONNECTION_ERROR
			}
			this.checkIfDemoServerIsActive()
		},
		async updateSettings(data) {
			this.updating = true
			try {
				const result = await axios.post(
					OC.filePath('officeonline', 'ajax', 'admin.php'),
					data,
				)
				this.updating = false
				return result
			} catch (e) {
				this.updating = false
				throw e
			}
		},
		checkIfDemoServerIsActive() {
			this.settings.demoUrl = this.demoServers ? this.demoServers.find((server) => server.demo_url === this.settings.wopi_url) : null
			this.settings.CODEUrl = this.CODEInstalled ? window.location.protocol + '//' + window.location.host + OC.filePath('officeonlinecode', '', '') + 'proxy.php?req=' : null
			if (this.settings.wopi_url && this.settings.wopi_url !== '') {
				this.serverMode = 'custom'
			}
			if (this.settings.demoUrl) {
				this.serverMode = 'demo'
				this.approvedDemoModal = true
			} else if (this.settings.CODEUrl && this.settings.CODEUrl === this.settings.wopi_url) {
				this.serverMode = 'builtin'
			}
		},
		demoServerLabel(server) {
			return `${server.provider_name} — ${server.provider_location}`
		},
		async setDemoServer(server) {
			this.settings.wopi_url = server.demo_url
			this.settings.disable_certificate_verification = false
			await this.updateServer()
		},
		async setBuiltinServer() {
			this.settings.wopi_url = this.settings.CODEUrl
			this.settings.disable_certificate_verification = false
			await this.updateServer()
		},
	},
}
</script>

<style lang="scss" scoped>
	p {
		margin-bottom: 15px;
	}

	p.checkbox-details {
		margin-left: 25px;
		margin-top: -10px;
		margin-bottom: 20px;
	}

	input[type='text'],
	.multiselect {
		width: 100%;
		max-width: 400px;
	}

	input#wopi_url {
		width: 300px;
	}

	#secure-view-settings {
		margin-top: 20px;
	}

	.section {
		border-bottom: 1px solid var(--color-border);
	}

	#security-warning-state-failure,
	#security-warning-state-warning,
	#security-warning-state-ok {
		margin-top: 10px;
		margin-bottom: 20px;
	}

	.option-inline {
		margin-left: 25px;
		&:not(.multiselect) {
			margin-top: 10px;
		}
	}

	.option-inline-emphasized {
		margin-left: 25px;
		&:not(.multiselect) {
			margin-top: 10px;
			font-style: italic;
		}

		ul {
			margin-bottom: 15px;
		}

		li {
			list-style: disc;
			padding: 3px;
			margin-left: 20px;
		}
	}

	.modal__content {
		margin: 20px;
		overflow: scroll;
		max-width: 600px;

		ul {
			margin-bottom: 15px;
		}

		li {
			list-style: disc;
			padding: 3px;
			margin-left: 20px;
		}

		button {
			float: right;
		}
	}
</style>
