import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import { runInNewContext } from 'node:vm';

/**
 * Load the actual admin bundle and invoke its registered change handler.
 * Only the jQuery operations needed by initialization and cache-key generation are stubbed.
 */
function changeCacheKey(bundle, { keys, handle = 'theme', knownHandles, checked = 1, total = 2, nodeName = 'INPUT', refresh = false }) {
	let value = keys.join(',');
	let generate;
	const document = { querySelectorAll: () => [] };
	const element = { nodeName, id: handle };
	const jQuery = (selector) => ({
		ready(callback) { callback(jQuery); },
		data(name) { return name === 'handle' ? handle : undefined; },
		attr(name) { return selector[name]; },
		on(event, callback) {
			if (selector === '.omgf-optimize-fonts-manage .unload, .omgf-optimize-fonts-manage .fallback-font-stack select') {
				generate = callback;
			}
		},
		val(next) {
			assert.equal(selector, '#cache_keys');
			if (next === undefined) return value;
			value = next;
		},
		length: selector === `#${handle} input.unload:checked` ? checked : total,
	});
	const math = Object.create(Math);
	math.random = () => 0.123456789;
	runInNewContext(readFileSync(new URL(`../../assets/js/${bundle}`, import.meta.url), 'utf8'), {
		jQuery, document, Math: math, setInterval() {},
		omgf_admin_i18n: knownHandles === undefined ? {} : { handles: knownHandles },
	});
	assert.equal(typeof generate, 'function', 'cache-key handler was registered');
	generate.call(element, refresh ? element : { target: element });
	return value.split(',');
}

for (const bundle of ['omgf-admin.js', 'omgf-admin.min.js']) {
	const cases = [
		['exact key after modified keys', {
			keys: ['theme-mod-old12', 'theme', 'theme-mod-other', 'theme-12'],
		}, ['theme-mod-4fzzz', 'theme-12']],
		['first modified key and duplicate cleanup', {
			keys: ['unrelated', 'theme-mod-old12', 'theme-mod-next1'],
		}, ['unrelated', 'theme-mod-4fzzz']],
		['known stylesheet handles are preserved', {
			keys: ['theme-mod-darkx', 'theme-mod-oldx', 'theme-mod-own12'],
			knownHandles: ['theme-mod-darkx', 'theme-mod-oldx'],
		}, ['theme-mod-darkx', 'theme-mod-oldx', 'theme-mod-4fzzz']],
		['unrelated and malformed keys are preserved', {
			keys: ['theme-12', 'other-theme', 'theme-mod-', 'theme-mod-UPPER', 'theme-mod-a_b', 'theme-mod-old12'],
		}, ['theme-12', 'other-theme', 'theme-mod-', 'theme-mod-UPPER', 'theme-mod-a_b', 'theme-mod-4fzzz']],
		['no matching key', { keys: ['theme-12', 'other-theme'] }, ['theme-12', 'other-theme']],
		['no fonts unloaded resets to exact key', {
			keys: ['theme-mod-old12', 'theme-mod-next1', 'theme-12'], checked: 0,
		}, ['theme', 'theme-12']],
		['all fonts unloaded resets to exact key', {
			keys: ['theme-mod-old12', 'theme-12'], checked: 2,
		}, ['theme', 'theme-12']],
		['fallback selection modifies even with no unloads', {
			keys: ['theme', 'theme-12'], checked: 0, nodeName: 'SELECT',
		}, ['theme-mod-4fzzz', 'theme-12']],
		['handle containing modification prefix stays intact', {
			keys: ['theme', 'theme-mod-dark-mod-old12'], handle: 'theme-mod-dark',
		}, ['theme', 'theme-mod-dark-mod-4fzzz']],
		['refresh uses the table handle', {
			keys: ['theme', 'theme-12'], refresh: true,
		}, ['theme-mod-4fzzz', 'theme-12']],
	];

	for (const [name, input, expected] of cases) {
		test(`${bundle}: ${name}`, () => {
			assert.deepEqual(changeCacheKey(bundle, input), expected);
		});
	}
}
