/**
 * @package OMGF
 * @author Daan van den Bergh
 */
// Start measuring CLS as early as possible, before the load event.
let omgf_font_cls = 0;
let omgf_fonts_ready = false;
let omgf_dom_content_loaded = document.readyState !== 'loading';
let omgf_cls_supported = false;

if (!omgf_dom_content_loaded) {
	document.addEventListener('DOMContentLoaded', () => {
		omgf_dom_content_loaded = true;
	});
}

// Feature detect layout-shift support before observing.
try {
	let test_observer = new PerformanceObserver(() => {
	});
	test_observer.observe({type: 'layout-shift', buffered: false});
	test_observer.disconnect();
	omgf_cls_supported = true;
} catch (e) {
	// layout-shift not supported in this browser.
}

if (omgf_cls_supported && typeof document.fonts !== 'undefined' && typeof document.fonts.ready !== 'undefined') {
	try {
		let omgf_cls_observer = new PerformanceObserver((list) => {
			list.getEntries().forEach((entry) => {
				// Only count shifts during the font swap window:
				// after DOMContentLoaded, before fonts.ready, without user input.
				if (!entry.hadRecentInput && omgf_dom_content_loaded && !omgf_fonts_ready) {
					omgf_font_cls += entry.value;
				}
			});
		});
		omgf_cls_observer.observe({type: 'layout-shift', buffered: true});

		// Stop observer once fonts are ready.
		document.fonts.ready.then(() => {
			omgf_fonts_ready = true;
			omgf_cls_observer.disconnect();
		});
	} catch (e) {
		// Observer setup failed.
	}
}

window.addEventListener('load', () => {
	let omgf_frontend = {
		menu_item: document.getElementById('wp-admin-bar-omgf'),
		sub_menu: document.getElementById('wp-admin-bar-omgf-default'),
		skip_results: omgf_frontend_results.skip || false,

		/**
		 * Run it all.
		 *
		 * @return void
		 */
		init: async function () {
			let response = null;
			let missing_preloads = [];
			let unused_fonts = [];
			let status = null;

			try {
				let google_fonts = this.filterGoogleFonts();
				response = await this.getStatus(google_fonts);
				let unused_fonts_analysis = response.unused_fonts_analysis || {};
				unused_fonts = response.unused_fonts || [];
				let preload_analysis = response.preload_analysis || {};
				missing_preloads = response.missing_preloads || [];
				let cls_analysis = response.cls_analysis || {};
				status = response.status || null;

				// menu_item only exists if the logged-in user has the manage_options cap.
				if (this.menu_item === null) {
					return;
				}

				this.menu_item.classList.add('dot');

				if (status) {
					this.menu_item.classList.add(status);
				}

				if ((status !== 'success' && status !== 'warning') && this.sub_menu !== null) {
					this.addInfoBox(status);
				}

				if (omgf_frontend.skip_results) {
					return;
				}

				if (omgf_frontend_i18n.multilang_plugin_used) {
					let count = omgf_frontend_i18n.subsets_count;

					if (count > 1) {
						let impact = omgf_frontend_i18n.info_box_impact_low;

						if (count === 3) {
							impact = omgf_frontend_i18n.info_box_impact_medium;
						} else if (count > 3) {
							impact = omgf_frontend_i18n.info_box_impact_high;
						}

						let multilang_plugin = {
							"name": omgf_frontend_i18n.multilang_plugin_name,
							"subsets_count": count,
							"impact": impact,
						}

						this.addInfoBox('multilang_plugin', multilang_plugin);
					}
				}

				if (unused_fonts_analysis && unused_fonts_analysis.count) {
					this.addInfoBox('unload_notice', unused_fonts_analysis);
				}

				if (preload_analysis && preload_analysis.potential_delay_ms > 0 && missing_preloads.length > 0) {
					this.addInfoBox('preload_notice', preload_analysis, missing_preloads.length);
				}

				if (cls_analysis && cls_analysis.cls > 0.01) {
					this.addInfoBox('cls_notice', cls_analysis);
				}

			} catch (error) {
				console.error('OMGF - Error running Google Fonts Checker:', error);
			} finally {
				document.dispatchEvent(new CustomEvent('omgf_frontend_loaded', {
					detail: {
						missing_preloads: missing_preloads,
						unused_fonts: unused_fonts,
						status: status,
					}
				}));
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
		 * Helper to get property value from a CSSRule, with fallback for Firefox.
		 *
		 * @param {CSSRule} rule
		 * @param {string} property
		 * @returns {string|null}
		 */
		getFontFaceProperty: function (rule, property) {
			let value = rule.style.getPropertyValue(property);

			if (value) {
				return value.replace(/["']/g, '').trim();
			}

			// Fallback: parse cssText (needed for Firefox)
			let match = rule.cssText.match(new RegExp(property + '\\s*:\\s*([^;]+)', 'i'));

			return match ? match[1].replace(/["']/g, '').trim() : null;
		},

		/**
		 * @param {CSSRuleList} rules
		 * @param {CSSStyleSheet} sheet
		 * @param {Map} font_face_url_map
		 * @param {Set} loaded_font_urls
		 */
		extractFontFaceRules: function (rules, sheet, font_face_url_map, loaded_font_urls) {
			for (let j = 0; j < rules.length; j++) {
				let rule = rules[j];

				// Recurse into @import rules.
				if (rule.type === CSSRule.IMPORT_RULE && rule.styleSheet) {
					let imported_rules = rule.styleSheet.cssRules || rule.styleSheet.rules;
					if (imported_rules) {
						this.extractFontFaceRules(imported_rules, rule.styleSheet, font_face_url_map, loaded_font_urls);
					}
				}

				if (rule.constructor.name === 'CSSFontFaceRule' || rule.type === CSSRule.FONT_FACE_RULE) {
					let rule_family = this.getFontFaceProperty(rule, 'font-family');
					let rule_weight = this.getFontFaceProperty(rule, 'font-weight') || '400';
					let rule_style = this.getFontFaceProperty(rule, 'font-style') || 'normal';

					if (rule_weight === 'normal') rule_weight = '400';
					if (rule_weight === 'bold') rule_weight = '700';

					let src = rule.style.getPropertyValue('src') || rule.style.src;
					if (!src) src = this.getFontFaceProperty(rule, 'src');

					let match = src ? src.match(/url\(["']?([^"')]+\.woff2[^"')]*?)["']?\)/) : null;

					// Fallback to any font URL if no woff2 found.
					if (!match) {
						match = src ? src.match(/url\(["']?([^"')]+)["']?\)/) : null;
					}

					if (!match) continue;

					let candidate_url = new URL(match[1], sheet.href || document.baseURI).href;
					let unicode_range = rule.style.getPropertyValue('unicode-range') || '';
					let key = `${rule_family}-${rule_weight}-${rule_style}${unicode_range ? '-' + unicode_range : ''}`.toLowerCase();

					// Pass 1: fallback to the first valid candidate URL.
					if (!font_face_url_map.has(key)) {
						font_face_url_map.set(key, candidate_url);
					}

					// Pass 2: prefer loaded URL.
					if (loaded_font_urls.has(candidate_url)) {
						font_face_url_map.set(key, candidate_url);
					}
				}
			}
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

				let used_faces_above_the_fold = new Set();
				let used_faces_entire_document = new Set();

				/**
				 * Both Missing Preloads and Unused Font Faces should detect font faces.
				 * Missing Preloads only scans Above The Fold, and Unused Font Faces scans the entire document.
				 */
				const elements = document.querySelectorAll('body *:not(#wpadminbar):not(#wpadminbar *)');
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
						let face_id = `${font}-${weight}-${font_style}`.toLowerCase();

						if (in_viewport) {
							used_faces_above_the_fold.add(face_id);
						}

						used_faces_entire_document.add(face_id);
					});

					['::before', '::after'].forEach((pseudo) => {
						let pseudo_style = window.getComputedStyle(element, pseudo);
						let pseudo_family = pseudo_style.fontFamily.replace(/["']/g, '');
						let pseudo_weight = pseudo_style.fontWeight;
						let pseudo_font_style = pseudo_style.fontStyle;

						pseudo_family.split(',').forEach((font) => {
							font = font.trim();
							let face_id = `${font}-${pseudo_weight}-${pseudo_font_style}`.toLowerCase();

							if (in_viewport) {
								used_faces_above_the_fold.add(face_id);
							}

							used_faces_entire_document.add(face_id);
						});
					});
				}

				let loaded_font_urls = new Set(
					window.performance.getEntriesByType('resource')
						.filter(e => e.name.match(/\.(woff2?|ttf|otf)(\?.*)?$/i))
						.map(e => e.name)
				);

				// Build font face URL map once.
				let font_face_url_map = new Map();

				for (let i = 0; i < document.styleSheets.length; i++) {
					try {
						let sheet = document.styleSheets[i];
						let rules = sheet.cssRules || sheet.rules;
						if (!rules) continue;

						this.extractFontFaceRules(rules, sheet, font_face_url_map, loaded_font_urls);
					} catch (e) {
						// Ignore cross-origin stylesheet errors.
					}
				}

				document.fonts.forEach((font) => {
					let family = font.family.replace(/["']/g, '');
					let weight = font.weight;
					let style = font.style;
					let face_id = `${family}-${weight}-${style}`.toLowerCase();
					let face_id_with_range = `${face_id}-${(font.unicodeRange || '')}`.toLowerCase();
					let font_url = font_face_url_map.get(face_id_with_range) || font_face_url_map.get(face_id);
					let effective_face_id = font_face_url_map.has(face_id_with_range) ? face_id_with_range : face_id;

					/**
					 * Scenario 2: Missing Preloads
					 *
					 * Check if any loaded fonts that are used above the fold are not preloaded.
					 */
					if (font.status === 'loaded' && font_url && (used_faces_above_the_fold.has(effective_face_id) || used_faces_above_the_fold.has(face_id))) {
						let is_preloaded = preloaded_fonts.some((url) => {
							// If we have the actual font URL, use it for exact matching.
							if (font_url && url === font_url) {
								return true;
							}

							let normalized_family = family.toLowerCase().replace(/\s/g, '-');
							let url_lower = url.toLowerCase();
							// Use regex with word boundaries or check for the exact segment match
							let pattern = new RegExp('(^|[/_-])' + normalized_family.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '([./_-]|$)', 'i');

							return pattern.test(url_lower);
						});

						if (!is_preloaded && !missing_preloads.some(f => f.family === family && f.url === font_url)) {
							missing_preloads.push({
								family: family,
								weight: weight,
								style: style,
								url: font_url
							});
						}
					}

					/**
					 * Scenario 3: Unused Fonts
					 *
					 * A font face is considered unused if it's explicitly defined in the stylesheet, but the browser never loaded it (status === 'unloaded').
					 */
					if (font.status === 'unloaded' && !used_faces_entire_document.has(effective_face_id) && font_url) {
						unused_fonts.push({
							family: family,
							weight: weight,
							style: style,
							url: font_url,
							unicode_range: font.unicodeRange
						});
					}
				});
			}

			const unused_fonts_analysis = this.analyzeUnusedFonts(unused_fonts);
			const preload_analysis = await this.analyzePreloadImpact(missing_preloads);
			const cls_analysis = this.analyzeCLS(omgf_font_cls);

			let data = new FormData();
			data.append('path', document.location.pathname);
			data.append('urls', JSON.stringify(google_fonts));
			data.append('params', JSON.stringify(params));
			data.append('unused_fonts_analysis', JSON.stringify(unused_fonts_analysis));
			data.append('preload_analysis', JSON.stringify(preload_analysis));
			data.append('cls_analysis', JSON.stringify(cls_analysis));

			return await omgf_frontend.ajax(data).then(response => {
				if (response) {
					response.unused_fonts_analysis = unused_fonts_analysis;
					response.unused_fonts = unused_fonts;
					response.preload_analysis = preload_analysis;
					response.missing_preloads = missing_preloads;
					response.cls_analysis = cls_analysis;
				}

				return response;
			});
		},

		/**
		 * @param {Array} faces
		 * @param {Array} font_resources
		 * @param {Function} callback
		 */
		forEachMatchingResources: function (faces, font_resources, callback) {
			faces.forEach((face) => {
				let family = face.family;
				let font_url = face.url;
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
					if (typeof face.weight === 'undefined') {
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
						// Match 'italic', '-i.woff', '_i.woff', or common patterns like '400i.woff'
						style_match = url_lower.includes('italic') || /[._-]i\.(woff|woff2|ttf|otf)/.test(url_lower) || /\d{3}i\.(woff|woff2|ttf|otf)/.test(url_lower);
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
		getFontResources: function () {
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
		analyzeUnusedFonts: function (unused_faces) {
			if (unused_faces.length === 0) {
				return {};
			}

			let count = unused_faces.length;
			let impact = omgf_frontend_i18n.info_box_impact_low;

			if (count > 6) {
				impact = omgf_frontend_i18n.info_box_impact_high;
			} else if (count >= 3) {
				impact = omgf_frontend_i18n.info_box_impact_medium;
			}

			return {
				count: count,
				impact: impact
			};
		},

		analyzeCLS: function (cls) {
			const MIN_CLS_THRESHOLD = 0.01;
			let impact = omgf_frontend_i18n.info_box_impact_low;

			if (cls < MIN_CLS_THRESHOLD) {
				return {};
			}

			if (cls > 0.1) {
				impact = omgf_frontend_i18n.info_box_impact_medium;
			}

			if (cls > 0.25) {
				impact = omgf_frontend_i18n.info_box_impact_high;
			}

			return {
				cls: Math.round(cls * 1000) / 1000,
				impact: impact,
			}
		},

		/**
		 * Analyze missing preloads for impact on LCP.
		 *
		 * @param {Array} missing_preloads
		 * @returns {Promise<Object>}
		 */
		analyzePreloadImpact: async function (missing_preloads) {
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
					has_text = (typeof lcp_element.innerText === 'string' && lcp_element.innerText.trim().length > 0)
						|| (typeof lcp_element.textContent === 'string' && lcp_element.textContent.trim().length > 0);
				}

				if (!has_text) {
					return result;
				}

				let font_resources = this.getFontResources();
				let max_delay = 0;

				this.forEachMatchingResources(missing_preloads, font_resources, (matching_entry) => {
					let delay = matching_entry.responseEnd - matching_entry.startTime;
					if (delay > 0) {
						result.affects_lcp = true;
						if (delay > max_delay) {
							max_delay = delay;
						}
					}
				});

				result.potential_delay_ms = Math.round(max_delay);

				// Ignore results under 5ms.
				if (result.potential_delay_ms <= 5) {
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

			if (status === 'multilang_plugin') {
				info_box.id = 'wp-admin-bar-omgf-multilang-info';
				let text = omgf_frontend.sprintf(omgf_frontend_i18n.info_box_multilang_plugin_text, omgf_frontend_i18n.multilang_plugin_name, data.subsets_count, data.impact);
				info_box.innerHTML = `<a class="ab-item" href="${omgf_frontend_i18n.info_box_admin_url}">${text}</a>`;
			}

			if (status === 'unload_notice') {
				info_box.id = 'wp-admin-bar-omgf-unload-info';
				let text = omgf_frontend.sprintf(omgf_frontend_i18n.info_box_unload_text, data.count || 0, data.impact || omgf_frontend_i18n.info_box_impact_low);
				info_box.innerHTML = `<a class="ab-item" href="${omgf_frontend_i18n.info_box_admin_url}">${text}</a>`;
			}

			if (status === 'preload_notice') {
				info_box.id = 'wp-admin-bar-omgf-preload-info';
				let text = omgf_frontend.sprintf(omgf_frontend_i18n.info_box_preload_text, count || 0, data.potential_delay_ms || 0, data.impact || omgf_frontend_i18n.info_box_impact_low);
				info_box.innerHTML = `<a class="ab-item" href="${omgf_frontend_i18n.info_box_admin_url}">${text}</a>`;
			}

			if (status === 'cls_notice') {
				info_box.id = 'wp-admin-bar-omgf-cls-info';
				let text = omgf_frontend.sprintf(omgf_frontend_i18n.info_box_cls_text, data.cls, data.impact);
				info_box.innerHTML = `<a class="ab-item" href="${omgf_frontend_i18n.info_box_admin_url}">${text}</a>`;
			}

			if (status === 'cls_notice' || status === 'unload_notice' || status === 'preload_notice' || status === 'multilang_plugin') {
				info_box.classList.add('info');
			}

			if (status === 'info' && omgf_frontend_i18n.first_run) {
				document.getElementById('wp-admin-bar-omgf-optimize-this')?.classList.add('info');
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
					default: {
						value = String(parseInt(value, 10));
						const n = Number(type.slice(1, -1));
						return '0'.repeat(n).slice(value.length) + value;
					}
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

