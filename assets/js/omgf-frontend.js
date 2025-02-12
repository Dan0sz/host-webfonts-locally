/**
 * @package OMGF
 * @author Daan van den Bergh
 */
window.addEventListener('load', () => {
	let omgf_frontend = {
		menu_item: document.getElementById('wp-admin-bar-omgf'),
		sub_menu: document.getElementById('wp-admin-bar-omgf-default'),

		/**
		 * Run it all.
		 */
		init: async function () {
			if (this.menu_item === null) {
				return;
			}

			this.menu_item.classList.add('dot');

			let google_fonts = this.filterGoogleFonts();
			let status = await this.getStatus(google_fonts);

			if (status && this.menu_item !== null) {
				this.menu_item.classList.add(status);
			}

			if ((status !== 'success' || status !== 'warning') && this.sub_menu !== null) {
				this.addInfoBox(status);
			}

			document.dispatchEvent(new Event('omgf_frontend_loaded'));
		},

		/**
		 * Filter the list of entries for calls to the Google Fonts API for further processing.
		 */
		filterGoogleFonts: () => {
			let entries = window.performance.getEntries();
			let google_fonts = entries.filter((entry) => entry.name.indexOf('/fonts.googleapis.com/css') > 0 || entry.name.indexOf('/fonts.gstatic.com/') > 0)

			if (google_fonts.length === 0) {
				return [];
			}

			console.log('OMGF has found the following Google Fonts API calls:');

			let urls = [];

			google_fonts.forEach((entry) => {
				urls.push(entry.name);

				console.log(' - ' + entry.name);
			})

			return urls;
		},

		/**
		 * Stores google_fonts in the DB and retrieves the status to be added to the admin bar classList.
		 *
		 * @param google_fonts
		 * @returns {*}
		 */
		getStatus: (google_fonts) => {
			const urlSearchParams = new URLSearchParams(window.location.search);
			const params = Object.fromEntries(urlSearchParams.entries());

			let action = 'omgf_admin_bar_status';
			let data = {'urls': google_fonts, 'path': document.location.pathname, '_wpnonce': omgf_frontend_i18n.nonce, 'params': params};
			let status = window.wp.ajax.send(action, {'data': data});

			return status.done((response) => {
				return response;
			});
		},

		/**
		 * Adds the info box to the submenu.
		 */
		addInfoBox: (status) => {
			let info_box = document.createElement('li');
			info_box.id = 'wp-admin-bar-omgf-info';

			if (status === 'alert') {
				info_box.innerHTML = `<a class="ab-item" href="${omgf_frontend_i18n.info_box_admin_url}">${omgf_frontend_i18n.info_box_alert_text}</a>`;
			}

			if (status === 'notice') {
				info_box.innerHTML = `<a class="ab-item" href="${omgf_frontend_i18n.info_box_admin_url}">${omgf_frontend_i18n.info_box_notice_text}</a>`;
			}

			omgf_frontend.sub_menu.prepend(info_box);
		},
	}

	// Make sure we've collected all resources before continuing.
	let entries = window.performance.getEntries();
	let interval = setInterval(() => {
		if (entries.length < window.performance.getEntries().length) {
			entries = window.performance.getEntries();
		}

		if (entries.length === window.performance.getEntries().length) {
			clearInterval(interval);

			omgf_frontend.init();
		}
	}, 500);
});

