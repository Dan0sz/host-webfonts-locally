<?php

namespace OMGF\Compatibility;

/**
 * @codeCoverageIgnore
 */
class Elementor extends CompatibilityHookRegistrar {
	/** @var string $hook */
	protected $hooks = [
		'elementor/core/files/clear_cache',
		'elementor/editor/after_save',
	];
}
