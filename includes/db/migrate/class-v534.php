<?php
defined('ABSPATH') || exit;

/**
 * @package   OMGF Pro
 * @author    Daan van den Bergh
 *            https://daan.dev
 * @copyright © 2022 Daan van den Bergh. All Rights Reserved.
 * @since     v5.3.4
 */
class OMGF_DB_Migrate_V534
{
    /** @var $version string The version number this migration script was introduced with. */
    private $version = '5.3.4';

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
        $optimized_fonts = OMGF::optimized_fonts() ?? [];
        $upgrade_req     = false;

        foreach ($optimized_fonts as $stylesheet => $fonts) {
            foreach ($fonts as $font) {
                $variants = $font->variants ?? [];

                foreach ($variants as $key => $variant) {
                    /**
                     * Optimized Fonts needs upgrading if $variants is still an indexed array.
                     * 
                     * @since v5.3.0 $variants should be an associative array.
                     */
                    if (is_numeric($key)) {
                        $upgrade_req = true;

                        break;
                    }
                }

                if ($upgrade_req) {
                    break;
                }
            }

            if ($upgrade_req) {
                break;
            }
        }

        /**
         * Mark cache as stale if upgrade is required.
         */
        if ($upgrade_req) {
            update_option(OMGF_Admin_Settings::OMGF_CACHE_IS_STALE, $upgrade_req);
        }

        /**
         * Update stored version number.
         */
        update_option(OMGF_Admin_Settings::OMGF_CURRENT_DB_VERSION, $this->version);
    }
}
