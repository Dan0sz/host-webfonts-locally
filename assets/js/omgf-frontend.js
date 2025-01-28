/**
 * @package OMGF
 * @author Daan van den Bergh
 */
document.addEventListener('DOMContentLoaded', () => {
	let status = 'success';

	const observer = new PerformanceObserver((list) => {
		let menu_item = document.getElementById('wp-admin-bar-omgf');

		list.getEntries().forEach((entry) => {
			let request_url = entry.name;

			if (request_url.indexOf('/fonts.googleapis.com/css') > 0 || request_url.indexOf('/fonts.gstatic.com/') > 0) {
				console.log('Request to Google Fonts API found: ' + request_url + '. Notifying user.')

				status = 'alert';

				// Also add a message to the submenu with more information. "Requests to Google Fonts API found. Click here for more information and how to resolve this."

				// Do an AJAX request to set a transient, so more information is displayed in the Task Manager, when the user navigates there.

				// Maybe check in the set transients, if something was already captured, and if not, display a general message? Maybe say something: contact me if you need more help?

				// That will lead to a surge in support requests, but also possible sales?
			}
		});

		menu_item.classList.add(status);
	});

	observer.observe({type: "resource", buffered: true});
});
