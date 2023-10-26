import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { loadState } from '@nextcloud/initial-state'
import { getCurrentUser } from '@nextcloud/auth'

let config

try {
	config = loadState('core', 'config')
} catch (e) {
	// This fallback is just for our legacy jsunit tests since we have no way to mock loadState calls
	config = OC.config
}

const keepSessionAlive = () => {
	return config.session_keepalive === undefined || !!config.session_keepalive
}

const getInterval = () => {
	let interval = NaN
	if (config.session_lifetime) {
		interval = Math.floor(config.session_lifetime / 2)
	}

	// minimum one minute, max 24 hours, default 15 minutes
	return Math.min(
		24 * 3600,
		Math.max(
			60,
			isNaN(interval) ? 900 : interval
		)
	)
}

export default {
	data() {
		return {
			autoLogoutInterval: null,
		}
	},
	mounted() {
		if (!config.auto_logout || !getCurrentUser() || keepSessionAlive()) {
			return
		}

		this.autoLogoutInterval = setInterval(this.autoLogoutActiveCallback, 1000 * getInterval())
	},
	destroyed() {
		if (this.autoLogoutInterval) {
			clearInterval(this.autoLogoutInterval)
		}
	},
	methods: {
		autoLogoutActiveCallback() {
			// Extend the session and avoid auto logout while editing
			axios.get(generateUrl('/apps/officeonline/ping'))
			localStorage.setItem('lastActive', Date.now().toString())
		},
	},
}
