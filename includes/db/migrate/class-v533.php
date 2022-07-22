<?php
defined('ABSPATH') || exit;

/**
 * @package   OMGF Pro
 * @author    Daan van den Bergh
 *            https://daan.dev
 * @copyright © 2022 Daan van den Bergh. All Rights Reserved.
 * @since     v3.6.0
 */
class OMGF_DB_Migrate_V533
{
    /** @var $version string The version number this migration script was introduced with. */
    private $version = '5.3.3';

    /**
     * Buid
     * 
     * @return void 
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Initialize
     * 
     * @return void 
     */
    private function init()
    {
        $subsets = get_option(OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_SUBSETS);

        if (!$subsets) {
            update_option(OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_SUBSETS, ['latin', 'latin-ext']);
        }

        /**
         * Update stored version number.
         */
        update_option(OMGF_Admin_Settings::OMGF_CURRENT_DB_VERSION, $this->version);
    }
}
