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
		 *
		 * @return void
		 */
		init: async function () {
			try {
				// menu_item only exists if the logged-in user has the manage_options cap.
				if (this.menu_item === null) {
					return;
				}

				let google_fonts = this.filterGoogleFonts();
				let response = await this.getStatus(google_fonts);

				if (!response) {
					return;
				}

				let status = response.status;
				let unused_fonts_analysis = response.unused_fonts_analysis || {};
				let preload_analysis = response.preload_analysis || {};
				let missing_preloads = response.missing_preloads || [];


				this.menu_item.classList.add('dot');

				if (status) {
					this.menu_item.classList.add(status);
				}

				if (unused_fonts_analysis && unused_fonts_analysis.total_kb) {
					this.addInfoBox('unload_notice', unused_fonts_analysis);
				}

				if (preload_analysis && preload_analysis.impact && missing_preloads.length > 0) {
					this.addInfoBox('preload_notice', preload_analysis, missing_preloads ? missing_preloads.length : 0);
				}
			} catch (error) {
				console.error('OMGF - Error running Google Fonts Checker:', error);
			} finally {
				document.dispatchEvent(new Event('omgf_frontend_loaded'));
			}
		},

		/**
		 * Filter the list of entries for calls to the Google Fonts API for further processing.
		 *
		 * @return array
		 */
		filterGoogleFonts: () => {
			let entries = window.performance.getEntries();
			let google_fonts = entries.filter((entry) => entry.name.indexOf('/fonts.googleapis.com/css') > 0 || entry.name.indexOf('/fonts.googleapis.com/icon') > 0 || entry.name.indexOf('/fonts.gstatic.com/') > 0)

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
		getStatus: async function (google_fonts) {
			const urlSearchParams = new URLSearchParams(window.location.search);
			const params = Object.fromEntries(urlSearchParams.entries());
			let missing_preloads = [];
			let unused_fonts = [];

			if (typeof document.fonts !== 'undefined' && typeof document.fonts.ready !== 'undefined') {
				await document.fonts.ready;

				let preloaded_fonts = [];
				let links = document.head.querySelectorAll('link[rel="preload"][as="font"]');

				links.forEach((link) => {
					preloaded_fonts.push(link.href);
				});

				let used_faces_above_the_fold = [];
				let used_faces_entire_document = [];

				/**
				 * Both Missing Preloads and Unused Font Faces should detect font faces.
				 * Missing Preloads only scans Above The Fold, and Unused Font Faces scans the entire document.
				 */
				const elements = document.querySelectorAll('body *');
				const scan_limit = 1500; // keep analysis bounded on very large DOMs
				for (let i = 0; i < elements.length && i < scan_limit; i++) {
					const element = elements[i];
					let rect = element.getBoundingClientRect();
					let style = window.getComputedStyle(element);
					let family = style.fontFamily.replace(/["']/g, '');
					let weight = style.fontWeight;
					let font_style = style.fontStyle;

					// If the element is in the viewport.
					let in_viewport = rect.top < window.innerHeight && rect.bottom > 0 && rect.left < window.innerWidth && rect.right > 0;

					// Handle font-family stacks (e.g. "Open Sans", sans-serif).
					family.split(',').forEach((font) => {
						font = font.trim();
						let face_id = `${font}-${weight}-${font_style}`;

						if (in_viewport && !used_faces_above_the_fold.includes(face_id)) {
							used_faces_above_the_fold.push(face_id);
						}

						if (!used_faces_entire_document.includes(face_id)) {
							used_faces_entire_document.push(face_id);
						}
					});
				}

				document.fonts.forEach((font) => {
					let family = font.family.replace(/["']/g, '');
					let weight = font.weight;
					let style = font.style;
					let face_id = `${family}-${weight}-${style}`;
					let font_src = font.src ? font.src.match(/url\("?(.+?)"?\)/) : null;
					let font_url = font_src ? font_src[1] : '';

					/**
					 * Scenario 2: Missing Preloads
					 *
					 * Check if any loaded fonts that are used above the fold are not preloaded.
					 */
					if (font.status === 'loaded' && used_faces_above_the_fold.includes(face_id)) {
						let is_preloaded = preloaded_fonts.some((url) => {
							// If we have the actual font URL, use it for exact matching.
							if (font_url && url === font_url) {
								return true;
							}

							let normalized_family = family.toLowerCase().replace(/\s/g, '-');
							let url_lower = url.toLowerCase();
							// Use regex with word boundaries or check for the exact segment match
							let pattern = new RegExp('(^|[/_-])' + normalized_family.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '([._-]|\\.|$)', 'i');

							return pattern.test(url_lower);
						});

						if (!is_preloaded && !missing_preloads.includes(family)) {
							missing_preloads.push(family);
						}
					}

					/**
					 * Scenario 3: Unused Fonts
					 *
					 * A font face is considered unused if it's loaded but not used ANYWHERE in the document.
					 */
					if (font.status === 'loaded' && !used_faces_entire_document.includes(face_id)) {
						unused_fonts.push({
							family: family,
							weight: weight,
							style: style,
							url: font_url
						});
					}
				});
			}

			const unused_fonts_analysis = this.analyze_unused_fonts(unused_fonts);
			const preload_analysis = await this.analyze_preload_impact(missing_preloads);

			let data = new FormData();
			data.append('path', document.location.pathname);
			data.append('urls', JSON.stringify(google_fonts));
			data.append('params', JSON.stringify(params));
			data.append('unused_fonts_analysis', JSON.stringify(unused_fonts_analysis));
			data.append('preload_analysis', JSON.stringify(preload_analysis));

			return await omgf_frontend.ajax(data).then(response => {
				if (response) {
					response.unused_fonts_analysis = unused_fonts_analysis;
					response.preload_analysis = preload_analysis;
					response.missing_preloads = missing_preloads;
				}

				return response;
			});
		},

		/**
		 * @param {Array} faces
		 * @param {Array} font_resources
		 * @param {Function} callback
		 */
		for_each_matching_resource: function (faces, font_resources, callback) {
			faces.forEach((face) => {
				let family = typeof face === 'string' ? face : face.family;
				let font_url = typeof face === 'object' ? face.url : '';
				let normalized_family = family.toLowerCase().replace(/\s/g, '-');

				let matching_entries = font_resources.filter((entry) => {
					let url_lower = entry.name.toLowerCase();

					// If we have an exact URL match, prioritize it.
					if (font_url && entry.name === font_url) {
						return true;
					}

					let pattern = new RegExp('(^|[/_-])' + normalized_family.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '([./_-]|\\.|$)', 'i');

					if (!pattern.test(url_lower)) {
						return false;
					}

					// If we only have a family (e.g. for preload check), stop here.
					if (typeof face === 'string') {
						return true;
					}

					/**
					 * For unused font analysis, we want to match the specific face (weight/style).
					 */
					let weight = face.weight;
					let style = face.style;

					// Map common font weight names to numbers if needed (document.fonts already uses numbers mostly)
					// Handle cases like "normal", "bold", etc.
					if (weight === 'normal') weight = '400';
					if (weight === 'bold') weight = '700';

					// Check if the URL contains the weight.
					let weight_pattern = new RegExp(`[./_-]${weight}([./_-]|\\.|$)`, 'i');

					// Check for italic.
					let style_match = true;
					if (style === 'italic') {
						style_match = url_lower.includes('italic') || url_lower.includes('i.woff'); // Handle some minified naming
					} else if (style === 'normal') {
						style_match = !url_lower.includes('italic');
					}

					return weight_pattern.test(url_lower) && style_match;
				});

				matching_entries.forEach((matching_entry) => {
					callback(matching_entry, family);
				});
			});
		},

		/**
		 * @returns {Array}
		 */
		get_font_resources: function () {
			let entries = window.performance.getEntriesByType("resource");
			return entries.filter((entry) => {
				return entry.name.match(/\.(woff|woff2|ttf|otf)(\?.*)?$/i);
			});
		},

		/**
		 * Analyze unused fonts for impact.
		 *
		 * @param {Array} unused_faces
		 * @returns {Object}
		 */
		analyze_unused_fonts: function (unused_faces) {
			if (unused_faces.length === 0) {
				return {};
			}

			let result = {
				total_kb: 0,
				count: 0,
				impact: omgf_frontend_i18n.info_box_impact_low,
				files: []
			};

			let font_resources = this.get_font_resources();

			this.for_each_matching_resource(unused_faces, font_resources, (matching_entry, family) => {
				// Avoid double-counting the same file.
				if (result.files.some((file) => file.url === matching_entry.name)) {
					return;
				}

				let size = matching_entry.transferSize || matching_entry.encodedBodySize || 0;
				let kb = Math.round((size / 1024) * 10) / 10;

				result.total_kb += kb;
				result.count++;
				result.files.push({
					family: family,
					url: matching_entry.name,
					kb: kb
				});
			});

			if (result.count === 0) {
				return result;
			}

			result.total_kb = Math.round(result.total_kb * 10) / 10;

			// Ignore results under 20KB.
			if (result.total_kb <= 20) {
				return {};
			}

			if (result.total_kb > 80) {
				result.impact = omgf_frontend_i18n.info_box_impact_high;
			} else if (result.total_kb >= 50) {
				result.impact = omgf_frontend_i18n.info_box_impact_medium;
			}

			return result;
		},

		/**
		 * Analyze missing preloads for impact on LCP.
		 *
		 * @param {Array} missing_preloads
		 * @returns {Promise<Object>}
		 */
		analyze_preload_impact: async function (missing_preloads) {
			if (missing_preloads.length === 0) {
				return {};
			}

			let result = {
				affects_lcp: false,
				potential_delay_ms: 0,
				impact: omgf_frontend_i18n.info_box_impact_low
			};

			try {
				let lcp_entry = await new Promise((resolve) => {
					let observer = new PerformanceObserver((list) => {
						let entries = list.getEntries();
						let last_entry = entries[entries.length - 1];
						observer.disconnect();
						resolve(last_entry);
					});
					observer.observe({type: 'largest-contentful-paint', buffered: true});

					// Timeout after 2 seconds if no LCP found.
					setTimeout(() => {
						observer.disconnect();
						resolve(null);
					}, 2000);
				});

				if (!lcp_entry) {
					return result;
				}

				// Determine if LCP element contains visible text.
				let lcp_element = lcp_entry.element;
				let has_text = false;

				if (lcp_element) {
					has_text = typeof lcp_element.innerText === 'string' && lcp_element.innerText.trim().length > 0;
				}

				if (!has_text) {
					return result;
				}

				let font_resources = this.get_font_resources();
				let max_delay = 0;

				this.for_each_matching_resource(missing_preloads, font_resources, (matching_entry) => {
					let delay = matching_entry.responseEnd - lcp_entry.startTime;
					if (delay > 0) {
						result.affects_lcp = true;
						if (delay > max_delay) {
							max_delay = delay;
						}
					}
				});

				result.potential_delay_ms = Math.round(max_delay);

				// Ignore results under 10ms.
				if (result.potential_delay_ms <= 10) {
					return {};
				}

				if (result.potential_delay_ms > 100) {
					result.impact = omgf_frontend_i18n.info_box_impact_high;
				} else if (result.potential_delay_ms > 20) {
					result.impact = omgf_frontend_i18n.info_box_impact_medium;
				}
			} catch (e) {
				console.warn('OMGF - Error analyzing preload impact:', e);
				return {};
			}

			return result;
		},

		/**
		 * Do AJAX request.
		 *
		 * @param data
		 *
		 * @return object
		 */
		ajax: function (data) {
			return fetch(
				omgf_frontend_i18n.api_url,
				{
					method: 'POST',
					credentials: 'same-origin',
					headers: {
						'X-WP-Nonce': omgf_frontend_i18n.nonce,
					},
					body: data,
				}
			).then(response => response.ok ? response.json() : false);
		},

		/**
		 * Adds the info box to the submenu.
		 */
		addInfoBox: function (status, data, count) {
			let info_box = document.createElement('li');
			info_box.id = 'wp-admin-bar-omgf-info';

			if (status === 'alert') {
				info_box.innerHTML = `<a class="ab-item" href="${omgf_frontend_i18n.info_box_admin_url}">${omgf_frontend_i18n.info_box_alert_text}</a>`;
			}

			if (status === 'notice') {
				info_box.innerHTML = `<a class="ab-item" href="${omgf_frontend_i18n.info_box_admin_url}">${omgf_frontend_i18n.info_box_notice_text}</a>`;
			}

			if (status === 'unload_notice') {
				info_box.id = 'wp-admin-bar-omgf-unload-info';
				let text = omgf_frontend.sprintf(omgf_frontend_i18n.info_box_unload_text, data.total_kb || 0, data.count || 0, data.impact || omgf_frontend_i18n.info_box_impact_low);
				info_box.innerHTML = `<a class="ab-item" href="${omgf_frontend_i18n.info_box_admin_url}">${text}</a>`;
			}

			if (status === 'preload_notice') {
				info_box.id = 'wp-admin-bar-omgf-preload-info';
				let text = omgf_frontend.sprintf(omgf_frontend_i18n.info_box_preload_text, data.potential_delay_ms || 0, count || 0, data.impact || omgf_frontend_i18n.info_box_impact_low);
				info_box.innerHTML = `<a class="ab-item" href="${omgf_frontend_i18n.info_box_admin_url}">${text}</a>`;
			}

			if (status === 'unload_notice' || status === 'preload_notice') {
				info_box.classList.add('info');
			}

			if (!omgf_frontend.sub_menu) {
				return;
			}

			omgf_frontend.sub_menu.prepend(info_box);
		},

		/**
		 * Sprintf JS polyfill.
		 *
		 * @param str
		 * @returns {*}
		 */
		sprintf: function (str) {
			let args = arguments, i = 1;

			return str.replace(/%(s|d|0\d+d)/g, function (x, type) {
				let value = args[i++];
				switch (type) {
					case 's':
						return value;
					case 'd':
						return parseInt(value, 10);
					default:
						value = String(parseInt(value, 10));
						const n = Number(type.slice(1, -1));
						return '0'.repeat(n).slice(value.length) + value;
				}
			});
		}
	}

	// Make sure we've collected all resources before continuing.
	let entries = window.performance.getEntries();
	let attempts = 0;
	const MAX_ATTEMPTS = 20;
	let interval = setInterval(() => {
		attempts++;

		if (entries.length < window.performance.getEntries().length) {
			entries = window.performance.getEntries();
		}

		if (entries.length === window.performance.getEntries().length || attempts >= MAX_ATTEMPTS) {
			clearInterval(interval);

			omgf_frontend.init();
		}
	}, 500);
});

