/**
 * Cache-key changes through the admin form's registered change handler.
 * Uses Node's built-in test runner; no browser or additional packages required.
 */
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import { runInNewContext } from 'node:vm';

function changeCacheKey(file, { keys, handle = 'theme', checked = 1, total = 2, nodeName = 'INPUT', handles, refresh = false }) {
	let value = keys;
	let change;
	const document = { querySelectorAll: () => [] };
	const field = { nodeName };
	const jquery = (selector) => ({
		ready: (callback) => callback(jquery),
		on: (event, callback) => {
			if (selector === '.omgf-optimize-fonts-manage .unload, .omgf-optimize-fonts-manage .fallback-font-stack select') {
				change = callback;
			}
		},
		data: (name) => name === 'handle' ? handle : undefined,
		attr: (name) => name === 'id' ? handle : undefined,
		val: (...args) => args.length ? (value = args[0]) : value,
		length: selector === `#${handle} input.unload:checked` ? checked : total,
	});
	const math = Object.create(Math);
	math.random = () => 0.5; // Base 36 produces the deterministic suffix "i".
	runInNewContext(readFileSync(new URL(`../../../${file}`, import.meta.url), 'utf8'), {
		jQuery: jquery, document, Math: math, setInterval: () => 0,
		omgf_admin_i18n: handles === undefined ? {} : { handles },
	});
	assert.equal(typeof change, 'function', 'The form must register its change handler');
	change.call(field, refresh ? { id: handle } : { target: field });
	return value;
}

for (const file of ['assets/js/omgf-admin.js', 'assets/js/omgf-admin.min.js']) {
	const cases = [
		['exact key takes precedence and obsolete modified keys disappear',
			{ keys: 'theme-mod-old12,other,theme,theme-mod-older' }, 'other,theme-mod-i'],
		['first modified key is retained at its position',
			{ keys: 'other,theme-mod-old12,theme-mod-older' }, 'other,theme-mod-i'],
		['overlapping handles stay unchanged',
			{ keys: 'theme12-mod-abc12,theme', handles: ['theme12', 'theme'] }, 'theme12-mod-abc12,theme-mod-i'],
		['known stylesheet keys are preserved',
			{ keys: 'theme-mod-dark,theme-mod-old,theme-mod-own12', handles: ['theme-mod-dark', 'theme-mod-old'] },
			'theme-mod-dark,theme-mod-old,theme-mod-i'],
		['invalid suffixes and embedded handles are preserved',
			{ keys: 'theme-mod-,theme-mod-ABC,theme-mod-a-b,other-theme-mod-abc,theme' },
			'theme-mod-,theme-mod-ABC,theme-mod-a-b,other-theme-mod-abc,theme-mod-i'],
		['no match leaves keys unchanged', { keys: 'theme12,other' }, 'theme12,other'],
		['no fonts unloaded resets to the original key',
			{ keys: 'theme-mod-old12,other,theme-mod-older', checked: 0 }, 'theme,other'],
		['all fonts unloaded resets to the original key',
			{ keys: 'theme-mod-old12,other', checked: 2 }, 'theme,other'],
		['fallback select still modifies a fully loaded stylesheet',
			{ keys: 'theme,other', nodeName: 'SELECT', checked: 0 }, 'theme-mod-i,other'],
		['handles containing the cache prefix retain their full name',
			{ keys: 'theme,theme-mod-dark-mod-old12', handle: 'theme-mod-dark' }, 'theme,theme-mod-dark-mod-i'],
		['refresh uses the stylesheet row handle',
			{ keys: 'theme,other', refresh: true }, 'theme-mod-i,other'],
	];
	for (const [name, input, expected] of cases) {
		test(`${file}: ${name}`, () => assert.equal(changeCacheKey(file, input), expected));
	}
}
