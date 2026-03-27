<?php

namespace OMGF\Compatibility;

/**
 * @codeCoverageIgnore
 */
class Oxygen extends CompatibilityHookRegistrar {
	/** @var array $hooks */
	protected $hooks = [ 'oxygen_vsb_post_compiled' ];
}
