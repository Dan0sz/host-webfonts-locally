/**
 * @package OMGF
 * @author Daan van den Bergh
 */
document.addEventListener('DOMContentLoaded', () => {
	let omgf_frontend = {
		status: 'success',
		menu_item: document.getElementById('wp-admin-bar-omgf'),
		sub_menu: document.getElementById('wp-admin-bar-omgf-default'),
		google_fonts: [],
		google_fonts_xhr: false,
		run: true,

		/**
		 * Run it all.
		 */
		init: function () {
			const observer = new PerformanceObserver(this.checkGoogleFonts);

			if (this.run) {
				observer.observe({type: "resource", buffered: true});
			}
		},

		/**
		 * Checks for requests to the Google Fonts API in the list of Network Requests.
		 *
		 * This checker can't detect requests in iframes.
		 *
		 * @param list
		 */
		checkGoogleFonts: function (list) {
			list.getEntries().forEach((entry) => {
				let request_url = entry.name;

				if (request_url.indexOf('/fonts.googleapis.com/css') > 0 || request_url.indexOf('/fonts.gstatic.com/') > 0) {
					console.log('Request to Google Fonts API found: ' + request_url + '.');

					if (!omgf_frontend.google_fonts.includes(request_url)) {
						omgf_frontend.google_fonts.push(request_url);
					}

					omgf_frontend.status = 'alert';

					// Maybe check in the set transients, if something was already captured, and if not, display a general message? Maybe say something: contact me if you need more help?

					// That will lead to a surge in support requests, but also possible sales?

					// Add option to enable it on every page, instead of just for administrators.
				}

			});

			omgf_frontend.menu_item.classList.add(omgf_frontend.status);
			omgf_frontend.storeResults(omgf_frontend.google_fonts);
			omgf_frontend.run = false;

			let sub_menu_item = document.getElementById('wp-admin-bar-omgf-info');

			if (omgf_frontend.status === 'alert' && sub_menu_item === null) {
				let info_box = document.createElement('li');
				info_box.innerHTML = `<li id="wp-admin-bar-omgf-info"><a class="ab-item" href="${omgf_frontend_i18n.info_box_admin_url}">${omgf_frontend_i18n.info_box_text}</a><li>`;
				omgf_frontend.sub_menu.prepend(info_box);
			}
		},

		/**
		 * Do an AJAX request to set a transient, containing the URL, to be read by the Task Manager.
		 *
		 * @param urls An array of Google Fonts requests.
		 */
		storeResults: function (urls) {
			if (omgf_frontend.google_fonts_xhr) {
				return;
			}

			let action = 'omgf_store_checker_results';
			let results = {'urls': urls, 'path': document.location.pathname, '_wpnonce': omgf_frontend_i18n.nonce};

			omgf_frontend.google_fonts_xhr = window.wp.ajax.send(action, {'data': results});
			omgf_frontend.google_fonts_xhr.done(function (response) {
				console.log(response);
			});
		}
	}

	omgf_frontend.init();
});
