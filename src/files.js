import { getCapabilities } from '@nextcloud/capabilities'
import './viewer.js'
import Vue from 'vue'
import Office from './view/Office'

import './css/icons.css'

// eslint-disable-next-line
__webpack_nonce__ = btoa(window.OC.requestToken)

// eslint-disable-next-line
__webpack_public_path__ = window.OC.linkTo('officeonline', 'js/')

Vue.prototype.t = window.t
Vue.prototype.n = window.n
Vue.prototype.OC = window.OC
Vue.prototype.OCA = window.OCA

document.addEventListener('DOMContentLoaded', () => {
	// PUBLIC SHARE LINK HANDLING
	const isPublic = document.getElementById('isPublic') ? document.getElementById('isPublic').value === '1' : false
	const mimetype = document.getElementById('mimetype') ? document.getElementById('mimetype').value : undefined
	const isSupportedMime = isPublic
		&& getCapabilities().officeonline.mimetypes.indexOf(mimetype) !== -1
		&& getCapabilities().officeonline.mimetypesNoDefaultOpen.indexOf(mimetype) === -1
	if (isSupportedMime) {
		/* eslint-disable-next-line no-new */
		new Vue({
			render: h => h(Office, { props: { fileName: document.getElementById('filename').value } }),
		}).$mount('#imgframe')
	}
})
