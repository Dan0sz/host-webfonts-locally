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
* @copyright: © 2023 Daan van den Bergh
* @url      : https://daan.dev
* * * * * * * * * * * * * * * * * * * */

namespace OMGF\Admin\Settings;

defined( 'ABSPATH' ) || exit;

class Help extends Builder {

	/**
	 *
	 * @return void
	 */
	public function __construct() {
		$this->title = __( 'Help & Documentation', 'host-webfonts-local' );

		// Title
		add_filter( 'omgf_help_content', [ $this, 'do_title' ], 10 );

		// Content
		add_filter( 'omgf_help_content', [ $this, 'do_content' ], 20 );
	}

	public function do_content() {
		$tweet_url = sprintf( 'https://twitter.com/intent/tweet?text=Thanks+to+OMGF+for+@WordPress,+my+Google+Fonts+are+GDPR+compliant!+Try+it+for+yourself:&via=Dan0sz&hashtags=GDPR,DSGVO,GoogleFonts,WordPress&url=%s', str_replace( ' ', '+', apply_filters( 'omgf_settings_page_title', 'OMGF' ) ), apply_filters( 'omgf_help_tab_plugin_url', 'https://wordpress.org/plugins/host-webfonts-local/' ) );
		?>
		<div class="postbox">
			<div class="content">
				<h2><?php echo sprintf( __( 'Thank you for using %s!', 'host-webfonts-local' ), apply_filters( 'omgf_settings_page_title', 'OMGF' ) ); ?></h2>
				<p class="about">
					<?php echo sprintf( __( 'Need help configuring %s? Please refer to the links below to get you started.', 'host-webfonts-local' ), apply_filters( 'omgf_settings_page_title', 'OMGF' ) ); ?>
				</p>
				<div class="column-container">
					<div class="column">
						<h3>
							<?php _e( 'Need Help?', 'host-webfonts-local' ); ?>
						</h3>
						<ul>
							<li><a target="_blank" href="<?php echo apply_filters( 'omgf_settings_help_quick_start', 'https://daan.dev/docs/omgf-pro/quick-start' ); ?>"><i class="dashicons dashicons-controls-forward"></i><?php echo __( 'Quick Start Guide', 'host-webfonts-local' ); ?></a></li>
							<li><a target="_blank" href="<?php echo apply_filters( 'omgf_settings_help_user_manual', 'https://daan.dev/docs/omgf-pro/' ); ?>"><i class="dashicons dashicons-text-page"></i><?php echo __( 'User Manual', 'host-webfonts-local' ); ?></a></li>
							<li><a target="_blank" href="<?php echo apply_filters( 'omgf_settings_help_faq_link', 'https://daan.dev/docs/omgf-pro-faq/' ); ?>"><i class="dashicons dashicons-editor-help"></i><?php echo __( 'FAQ', 'host-webfonts-local' ); ?></a></li>
							<li><a target="_blank" href="<?php echo apply_filters( 'omgf_settings_help_troubleshooting_link', 'https://daan.dev/docs/omgf-pro-troubleshooting/' ); ?>"><i class="dashicons dashicons-sos"></i><?php echo __( 'Troubleshooting Guide', 'host-webfonts-local' ); ?></a></li>
							<li><a target="_blank" href="<?php echo apply_filters( 'omgf_settings_help_support_link', 'https://daan.dev/contact/' ); ?>"><i class="dashicons dashicons-email"></i><?php echo __( 'Get Support', 'host-webfonts-local' ); ?></a></li>
						</ul>
					</div>
					<div class="column">
						<h3><?php echo sprintf( __( 'Support %s & Spread the Word!', 'host-webfonts-local' ), apply_filters( 'omgf_settings_page_title', 'OMGF' ) ); ?></h3>
						<ul>
							<li><a target="_blank" href="<?php echo apply_filters( 'omgf_help_tab_review_link', 'https://wordpress.org/support/plugin/host-webfonts-local/reviews/?rate=5#new-post' ); ?>"><i class="dashicons dashicons-star-filled"></i><?php echo __( 'Write a 5-star Review or,', 'host-webfonts-local' ); ?></a></li>
							<li><a target="_blank" href="<?php echo $tweet_url; ?>"><i class="dashicons dashicons-twitter"></i><?php echo __( 'Tweet about it!', 'host-webfonts-local' ); ?></a></li>
						</ul>
					</div>
					<div class="column last">
						<h3 class="signature"><?php echo sprintf( __( 'Coded with %s by', 'host-webfonts-local' ), '❤️' ); ?> </h3>
						<p class="signature">
							<a target="_blank" title="<?php echo __( 'Visit Daan.dev', 'host-webfonts-local' ); ?>" href="https://daan.dev/wordpress-plugins/"><img class="signature-image" alt="<?php echo __( 'Visit Daan.dev', 'host-webfonts-local' ); ?>" src="<?php echo plugin_dir_url( OMGF_PLUGIN_FILE ) . 'assets/images/logo.png'; ?>" /></a>
						</p>
					</div>
				</div>
			</div>
		</div>
		</div>
		<?php
	}
}
