<?php

namespace OMGF\Compatibility;

/**
 * @codeCoverageIgnore
 */
class Divi extends CompatibilityHookRegistrar {
	/** @var array $hooks */
	protected $hooks = [
		'et_core_static_resources_removed',
		'et_save_post',
	];
}
