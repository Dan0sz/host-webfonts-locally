/**
 * @package OMGF
 * @author Daan van den Bergh
 */
document.addEventListener('DOMContentLoaded', () => {
	let status = 'success';

	const observer = new PerformanceObserver((list) => {
		let menu_item = document.getElementById('wp-admin-bar-omgf');
		let sub_menu = document.getElementById('wp-admin-bar-omgf-default');

		list.getEntries().forEach((entry) => {
			let request_url = entry.name;

			if (request_url.indexOf('/fonts.googleapis.com/css') > 0 || request_url.indexOf('/fonts.gstatic.com/') > 0) {
				console.log('Request to Google Fonts API found: ' + request_url + '. Notifying user.')

				status = 'alert';

				let info_box = document.createElement('li');
				// Remember to localize the string and the admin-url.
				info_box.innerHTML = '<li id="wp-admin-bar-omgf-info"><a class="ab-item" href="">Requests to Google Fonts API found. Click here for more information.</a><li>';
				sub_menu.prepend(info_box);

				// Also add a message to the submenu with more information. "Requests to Google Fonts API found. Click here for more information and how to resolve this."

				// Do an AJAX request to set a transient, so more information is displayed in the Task Manager, when the user navigates there. Make sure to save the request URL.

				// Maybe check in the set transients, if something was already captured, and if not, display a general message? Maybe say something: contact me if you need more help?

				// That will lead to a surge in support requests, but also possible sales?
			}
		});

		menu_item.classList.add(status);
	});

	observer.observe({type: "resource", buffered: true});
});
