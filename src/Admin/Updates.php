<?php
namespace OMGF\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * A (kind of) portable file, which allows me to add some extra handling to updates for premium "daughters"
 * of freemium (mother) plugins.
 *
 * Basically, what this class does is make sure that, if automatic updates seem to be failing, the user is
 * informed (in a non-intrusive manner, i.e. the Plugins screen) of the fact that an update is indeed
 * available and where/how to download/install it manually.
 *
 * @package Daan/Updates
 */
class Updates {
	/** @var string $plugin_text_domain */
	private $plugin_text_domain = 'host-webfonts-local';

	/** @var array $premium_plugins */
	private $premium_plugins = [
		'4027' => [
			'basename'        => 'host-google-fonts-pro/host-google-fonts-pro.php',
			'transient_label' => 'omgf_pro',
		],
		'8887' => [
			'basename'        => 'omgf-additional-fonts/omgf-additional-fonts.php',
			'transient_label' => 'omgf_af',
		],
	];

	/**
	 * Action & Filter hooks.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'all_plugins', [ $this, 'maybe_display_premium_update_notice' ] );
		add_filter( 'wp_get_update_data', [ $this, 'maybe_add_update_count' ], 10, 1 );
	}

	/**
	 * This function checks if:
	 * - Premium plugin is installed,
	 * - And if so, if an update is already available for it.
	 * - And if not, if the current version is lower than the latest available version.
	 * - And if so, display a custom notice with instructions to download the update manually.
	 *
	 * @param mixed $installed_plugins
	 *
	 * @return mixed
	 */
	public function maybe_display_premium_update_notice( $installed_plugins ) {
		$plugin_slugs = array_keys( $installed_plugins );

		foreach ( $this->premium_plugins as $id => $premium_plugin ) {
			if ( ! in_array( $premium_plugin['basename'], $plugin_slugs ) ) {
				continue;
			}

			if ( $this->update_already_displayed( $premium_plugin['basename'] ) ) {
				continue;
			}

			$latest_version  = $this->get_latest_version( $id, $premium_plugin['transient_label'] );
			$current_version = get_plugin_data( WP_PLUGIN_DIR . '/' . $premium_plugin['basename'] )['Version'] ?? '';

			if ( version_compare( $current_version, $latest_version, '<' ) ) {
				$installed_plugins[ $premium_plugin['basename'] ]['update'] = true;

				add_action( 'after_plugin_row_' . $premium_plugin['basename'], [ $this, 'display_premium_update_notice' ], 10, 2 );
			}
		}

		return $installed_plugins;
	}

	/**
	 * Checks if there's already an update available for the premium plugin in the Plugins screen.
	 *
	 * @return bool
	 */
	private function update_already_displayed( $basename ) {
		$available_updates = $this->get_available_updates();

		if ( ! is_object( $available_updates ) ) {
			return false;
		}

		$plugin_slugs = array_keys( $available_updates->response );

		return in_array( $basename, $plugin_slugs );
	}

	/**
	 * Fetch available updates from database.
	 *
	 * @return mixed
	 */
	private function get_available_updates() {
		static $available_updates;

		if ( $available_updates === null ) {
			$available_updates = get_site_transient( 'update_plugins' );
		}

		return $available_updates;
	}

	/**
	 * Gets the latest available version of the current premium plugin.
	 */
	private function get_latest_version( $id, $transient_label ) {
		static $latest_version;

		/**
		 * This prevents duplicate DB reads.
		 */
		if ( $latest_version === null ) {
			$latest_version = get_transient( $transient_label . '_latest_available_version' );
		}

		/**
		 * If $latest_version is an empty string, that probably means something went wrong before. So,
		 * we should try and refresh it. If $latest_version is false, then the transient doesn't exist.
		 */
		if ( $latest_version === false || $latest_version === '' ) {
			$response       = wp_remote_get( 'https://daan.dev/?edd_action=get_version&item_id=' . $id );
			$latest_version = json_decode( wp_remote_retrieve_body( $response ) )->new_version ?? '';

			set_transient( $transient_label . '_latest_available_version', $latest_version, DAY_IN_SECONDS );
		}

		return $latest_version;
	}

	/**
	 * Display a notice if current version of premium plugin is outdated, but updates can't be retrieved.
	 *
	 * @action after_plugin_row_{plugin_basename}
	 *
	 * @param mixed $file
	 * @param mixed $plugin_data
	 * @param mixed $status
	 *
	 * @return void
	 */
	public function display_premium_update_notice( $file, $plugin_data ) {
		$slug   = $plugin_data['slug'];
		$label  = $plugin_data['name'] ?? 'this plugin';
		$notice = sprintf( __( 'An update for %1$s is available, but we\'re having trouble retrieving it. Download it from <a href=\'%2$s\' target=\'_blank\'>your account area</a> and install it manually. <a href=\'%3$s\' target=\'_blank\'>Need help</a>?', $this->plugin_text_domain ), $label, 'https://daan.dev/account/orders/', 'https://daan.dev/docs/pre-sales/download-files/' );

		/**
		 * This snippet of JS either overwrites the contents of the update message.
		 */
		?>
		<script>
			var row = document.getElementById('<?php echo esc_attr( $slug ); ?>-update');
			var div = row.getElementsByClassName('notice-warning');

			if (div instanceof HTMLCollection && "0" in div) {
				div[0].getElementsByTagName('p')[0].innerHTML = "<?php echo wp_kses( $notice, 'post' ); ?>";
			}
		</script>
		<?php
	}

	/**
	 * This function check if:
	 * - Premium plugin is installed,
	 * - And if so, if an update is already fetched and displayed by WP itself.
	 * - And if so, if the currently installed version is lower than the latest available version,
	 * - And if so, adds 1 to the little update nag next to "Plugins" in the sidebar to attract
	 *   attention to the fact that updates seem to be failing.
	 *
	 * @param mixed $update_data
	 * @param mixed $plugins
	 *
	 * @return mixed
	 *
	 * @throws InvalidArgument
	 */
	public function maybe_add_update_count( $update_data ) {
		// phpcs:ignore
		if ( isset( $_GET['plugin_status'] ) && $_GET['plugin_status'] === 'upgrade' ) {
			return $update_data;
		}

		foreach ( $this->premium_plugins as $id => $plugin ) {
			if ( ! is_plugin_active( WP_PLUGIN_DIR . '/' . $plugin['basename'] ) ) {
				continue;
			}

			if ( $this->update_already_displayed( $plugin['basename'] ) ) {
				continue;
			}

			$latest_version  = $this->get_latest_version( $id, $plugin['transient_label'] );
			$plugin_data     = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin['basename'] );
			$current_version = $plugin_data['Version'] ?? '';

			if ( version_compare( $current_version, $latest_version, '<' ) ) {
				$update_data['counts']['plugins']++;
			}
		}

		return $update_data;
	}
}
