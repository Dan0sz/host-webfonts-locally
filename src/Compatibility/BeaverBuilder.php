<?php

namespace OMGF\Compatibility;

/**
 * @codeCoverageIgnore
 */
class BeaverBuilder extends CompatibilityHookRegistrar {
	/** @var array $hooks */
	protected $hooks = [ 'fl_builder_after_save_layout' ];
}
