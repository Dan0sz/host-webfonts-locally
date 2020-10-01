<?php
/* * * * * * * * * * * * * * * * * * * * *
 *
 *  ██████╗ ███╗   ███╗ ██████╗ ███████╗
 * ██╔═══██╗████╗ ████║██╔════╝ ██╔════╝
 * ██║   ██║██╔████╔██║██║  ███╗█████╗
 * ██║   ██║██║╚██╔╝██║██║   ██║██╔══╝
 * ╚██████╔╝██║ ╚═╝ ██║╚██████╔╝██║
 *  ╚═════╝ ╚═╝     ╚═╝ ╚═════╝ ╚═╝
 *
 * @package  : OMGF
 * @author   : Daan van den Bergh
 * @copyright: (c) 2020 Daan van den Bergh
 * @url      : https://daan.dev
 * * * * * * * * * * * * * * * * * * * */

defined( 'ABSPATH' ) || exit;

class OMGF_Admin_Notice
{
	const OMGF_ADMIN_NOTICE_TRANSIENT  = 'omgf_admin_notice';
	const OMGF_ADMIN_NOTICE_EXPIRATION = 60;
	
	/** @var array $notices */
	public static $notices = [];
	
	private static $plugin_text_domain = 'host-webfonts-local';
	
	/**
	 * @param        $message
	 * @param string $type (info|warning|error|success)
	 * @param string $screen_id
	 * @param bool   $json
	 * @param int    $code
	 */
	public static function set_notice ( $message, $message_id = '', $die = true, $type = 'success', $code = 200, $screen_id = 'all' ) {
		self::$notices                                       = get_transient( self::OMGF_ADMIN_NOTICE_TRANSIENT );
		self::$notices[ $screen_id ][ $type ][ $message_id ] = $message;
		
		set_transient( self::OMGF_ADMIN_NOTICE_TRANSIENT, self::$notices, self::OMGF_ADMIN_NOTICE_EXPIRATION );
		
		if ( $die ) {
			switch ( $type ) {
				case 'error':
					wp_send_json_error( $message, $code );
					break;
				default:
					wp_send_json_success( $message, $code );
			}
		}
	}
	
	/**
	 * Prints notice (if any) grouped by type.
	 */
	public static function print_notices () {
		$admin_notices = get_transient( self::OMGF_ADMIN_NOTICE_TRANSIENT );
		
		if ( is_array( $admin_notices ) ) {
			$current_screen = get_current_screen();
			
			foreach ( $admin_notices as $screen => $notice ) {
				if ( $current_screen->id != $screen && $screen != 'all' ) {
					continue;
				}
				
				foreach ( $notice as $type => $message ) {
					?>
                    <div id="message" class="notice notice-<?php echo $type; ?> is-dismissible">
						<?php foreach ( $message as $line ): ?>
                            <p><?= $line; ?></p>
						<?php endforeach; ?>
                    </div>
					<?php
				}
			}
		}
		
		delete_transient( self::OMGF_ADMIN_NOTICE_TRANSIENT );
	}
	
	/**
	 *
	 */
	public static function optimization_finished () {
		if ( get_option( OMGF_Admin_Settings::OMGF_OPTIMIZATION_COMPLETE ) ) {
			return;
		}
		
		OMGF_Admin_Notice::set_notice(
			__( 'OMGF has finished optimizing your Google Fonts. Enjoy! :-)', self::$plugin_text_domain ),
			'omgf-optimize',
			false
		);
		
		OMGF_Admin_Notice::set_notice(
			'<em>' . __( 'If you\'re using any CSS minify/combine and/or Full Page Caching plugins, don\'t forget to flush their caches.', self::$plugin_text_domain ) . '</em>',
			'omgf-optimize-plugin-notice',
			false,
			'info'
		);
		
		OMGF_Admin_Notice::set_notice(
			__( 'OMGF will keep running silently in the background and will generate additional stylesheets when other Google Fonts are found on any of your pages.', self::$plugin_text_domain ),
			'omgf-optimize-background',
			false,
			'info'
		);
		
		update_option( OMGF_Admin_Settings::OMGF_OPTIMIZATION_COMPLETE, true );
	}
}
