import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import { runInNewContext } from 'node:vm';

// Exercise the registered change handler in the actual assets, without exporting private helpers
// or loading WordPress. Only the jQuery operations used during registration and key generation are stubbed.
function generateKey(asset, { keys, handle = 'theme', handles, checked = 1, total = 2, nodeName = 'INPUT', refresh = false }) {
	let value = keys;
	let handler;
	const control = { nodeName };
	const jquery = (selector) => ({
		ready(callback) { callback(jquery); },
		data(name) { return selector === control && name === 'handle' ? handle : undefined; },
		attr(name) { return name === 'id' ? handle : undefined; },
		on(event, callback) {
			if (event === 'change' && typeof selector === 'string' && selector.includes('.fallback-font-stack select')) {
				handler = callback;
			}
		},
		val(next) {
			assert.equal(selector, '#cache_keys');
			if (next !== undefined) value = next;
			return value;
		},
		length: selector === `#${handle} input.unload:checked` ? checked :
			selector === `#${handle} input.unload` ? total : 0,
	});
	const math = Object.create(Math);
	math.random = () => 0.5; // Base 36 yields "0.i", so generated keys end in "-mod-i".
	runInNewContext(readFileSync(new URL(`../../../assets/js/${asset}`, import.meta.url), 'utf8'), {
		jQuery: jquery,
		document: { querySelectorAll: () => [] },
		omgf_admin_i18n: handles === undefined ? {} : { handles },
		setInterval() {},
		Math: math,
	});
	assert.equal(typeof handler, 'function', 'The asset registers its cache key handler');
	handler.call(control, refresh ? { id: handle } : { target: control });
	return value;
}

// Each group represents one table cell: its hidden variants share a single visible checkbox.
function bindFontForm(asset, groups) {
	const variants = new Map(groups.flatMap(({ checkbox, fields }) => fields.map(field => [field, checkbox])));
	const handlers = [];
	const jquery = (selector) => ({
		ready(callback) { callback(jquery); },
		data() {},
		on(event, callback) {
			if (selector === '#omgf-optimize-settings-form' && event === 'submit') {
				handlers.push(callback);
			}
		},
		each(callback) {
			assert.equal(selector, '.omgf-optimize-fonts-manage input.variable-font-variant');
			[...variants.keys()].forEach((field, index) => callback(index, field));
		},
		siblings(query) {
			assert.equal(query, 'input[type="checkbox"]');
			assert.ok(variants.has(selector));
			return [variants.get(selector)];
		},
		append() {
			assert.equal(selector, '#wpwrap');
		},
	});
	runInNewContext(readFileSync(new URL(`../../../assets/js/${asset}`, import.meta.url), 'utf8'), {
		jQuery: jquery,
		document: { querySelectorAll: () => [] },
		setInterval() {},
	});
	return () => handlers.forEach(handler => handler());
}

const cases = [
	['exact key beats an earlier modified key', { keys: 'theme-mod-old,theme,other' }, 'theme-mod-i,other'],
	['first modified key wins and other owned keys are removed', { keys: 'theme-mod-old,other,theme-mod-next' }, 'theme-mod-i,other'],
	['overlapping handles remain untouched', { keys: 'theme-12,other-theme,theme' }, 'theme-12,other-theme,theme-mod-i'],
	['known handles own their names', { keys: 'theme-mod-dark,theme-mod-old,theme', handles: ['theme-mod-dark'] }, 'theme-mod-dark,theme-mod-i'],
	['known handles containing the prefix keep their full name', { keys: 'theme,theme-mod-dark-mod-old', handle: 'theme-mod-dark' }, 'theme,theme-mod-dark-mod-i'],
	['invalid suffixes remain untouched', { keys: 'theme-mod-,theme-mod-UPPER,theme-mod-a_b,theme-mod-a-b,theme-mod-a.b,theme' }, 'theme-mod-,theme-mod-UPPER,theme-mod-a_b,theme-mod-a-b,theme-mod-a.b,theme-mod-i'],
	['no selected unloads resets to the exact handle', { keys: 'theme-mod-old,other,theme-mod-next', checked: 0 }, 'theme,other'],
	['all selected unloads resets to the exact handle', { keys: 'theme-mod-old,other', checked: 2 }, 'theme,other'],
	['fallback selection modifies the key even without unloads', { keys: 'theme,other', checked: 0, nodeName: 'SELECT' }, 'theme-mod-i,other'],
	['refresh uses the table handle', { keys: 'theme,other', refresh: true }, 'theme-mod-i,other'],
	['no matching key leaves other stylesheets alone', { keys: 'theme-12,other' }, 'theme-12,other'],
	['an empty key list stays empty', { keys: '' }, ''],
];

for (const asset of ['omgf-admin.js', 'omgf-admin.min.js']) {
	for (const [description, input, expected] of cases) {
		test(`${asset}: ${description}`, () => {
			assert.equal(generateKey(asset, input), expected);
		});
	}

	test(`${asset}: submitting the form synchronizes each variable font control independently`, () => {
		const preload = { checked: true, disabled: false };
		const unload = { checked: true, disabled: true };
		const otherRow = { checked: false, disabled: false };
		const weight = (value) => ({ value: 'stale', dataset: { value } });
		const preloadFields = [weight('600'), weight('700italic')];
		const unloadFields = [weight('600'), weight('700italic')];
		const otherFields = [weight('900')];
		const submit = bindFontForm(asset, [
			{ checkbox: preload, fields: preloadFields },
			{ checkbox: unload, fields: unloadFields },
			{ checkbox: otherRow, fields: otherFields },
		]);
		const values = fields => fields.map(field => field.value);

		submit();
		assert.deepEqual(values(preloadFields), ['600', '700italic']);
		assert.deepEqual(values(unloadFields), ['0', '0'], 'A disabled but checked control is not submitted');
		assert.deepEqual(values(otherFields), ['0']);

		preload.checked = false;
		unload.disabled = false;
		otherRow.checked = true;
		submit();
		assert.deepEqual(values(preloadFields), ['0', '0'], 'Unchecking the row clears previously selected variants');
		assert.deepEqual(values(unloadFields), ['600', '700italic']);
		assert.deepEqual(values(otherFields), ['900']);
	});

	test(`${asset}: submitting a form without grouped variants succeeds`, () => {
		const submit = bindFontForm(asset, []);
		assert.doesNotThrow(submit);
	});
}
