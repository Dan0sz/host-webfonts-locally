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
			omgf_frontend.menu_item.classList.add('dot');

			let google_fonts = omgf_frontend.filterGoogleFonts();
			let status = await omgf_frontend.getStatus(google_fonts);

			if (status && omgf_frontend.menu_item !== null) {
				omgf_frontend.menu_item.classList.add(status);
			}

			if ((status !== 'success' || status !== 'warning') && omgf_frontend.sub_menu !== null) {
				omgf_frontend.addInfoBox(status);
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
			let action = 'omgf_admin_bar_status';
			let data = {'urls': google_fonts, 'path': document.location.pathname, '_wpnonce': omgf_frontend_i18n.nonce};
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

	// This timeout allows the window.performance object to complete.
	setTimeout(omgf_frontend.init, 1000);
});

