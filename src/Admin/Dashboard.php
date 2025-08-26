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
 * @copyright: © 2017 - 2025 Daan van den Bergh
 * @url      : https://daan.dev
 * * * * * * * * * * * * * * * * * * * */

namespace OMGF\Admin;

use OMGF\Helper as OMGF;

class Dashboard {
	/**
	 * @since v5.5.6 Plugins which can't run alongside OMGF, mostly plugins which remove Google Fonts.
	 */
	const INCOMPATIBLE_PLUGINS = [
		'disable-google-fonts',
		'disable-remove-google-fonts',
		'embed-google-fonts',
		'local-google-fonts',
		// 'use-bunnyfont-host-google-fonts' TODO: Since OMGF supports Bunny CDN, this should be tested.
	];

	/**
	 * @since v5.5.4 Plugins which require additional configuration to be compatible with OMGF Pro.
	 */
	const PLUGINS_ADDTNL_CONF = [
		'autoptimize',
		'borlabs-cookie',
		'essential-grid',
		'perfmatters',
		'real-cookie-banner',
		'thrive-visual-editor',
		'trustmary',
		'wp-optimize',
	];

	/**
	 * @since v5.4.0 List of template handles which require additional configuration to be compatible with OMGF.
	 */
	const THEMES_ADDTNL_CONF = [
		'Avada',
		'Divi',
		'Extra',
		'thrive-theme',
	];

	/**
	 * JS libraries loading Google Fonts in iframes.
	 */
	const IFRAMES_LOADING_FONTS = [
		'active-campaign'             => '.activehosted.com/f/embed.php', // ActiveCampaign
		'channext'                    => '//content.channext.com/js/', // Channext
		'conversio'                   => '//app.conversiobot.com', // Conversio
		'gastronovi'                  => '//services.gastronovi.com', // Gastronovi
		'google-ads'                  => '//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js', // Google Ads
		'google-campaign-manager-360' => '//www.googletagservices.com/dcm/dcmads.js', // Google Campaign Manager 360
		'google-maps'                 => '.google.com/maps', // Google Maps
		'hubspot'                     => '.hs-scripts.com/', // Hubspot
		'mailerlite'                  => 'https://assets.mailerlite.com/js/universal.js', // Mailerlite
		'manychat'                    => '//widget.manychat.com/', // ManyChat
		'recaptcha'                   => '//www.google.com/recaptcha/api.js', // Recaptcha
		'tawk.to'                     => '//embed.tawk.to', // Tawk.to
		'tidio'                       => '//code.tidio.co/', // Tidio
		'youtube'                     => '//www.youtube.com/embed/', // Youtube Embeds
	];

	/**
	 * Generates the HTML for the dashboard by rendering any warnings and capturing the output buffer.
	 *
	 * @return string The rendered dashboard HTML content.
	 *
	 * @codeCoverageIgnore
	 */
	public static function get_dashboard_html() {
		ob_start();

		self::render_warnings();

		return ob_get_clean();
	}

	/**
	 * Renders the Dashboard Warnings boxes.
	 *
	 * @codeCoverageIgnore
	 */
	public static function render_warnings() {
		if ( ! empty( OMGF::get_option( Settings::OMGF_OPTIMIZE_SETTING_TEST_MODE ) ) && ! wp_doing_ajax() ) : ?>
			<tr id="task-manager-notice-test-mode-row">
				<td colspan="2" class="task-manager-row">
					<div class="task-manager-notice info">
						<h4><?php echo esc_html__( 'Test Mode is Enabled', 'host-webfonts-local' ); ?></h4>
						<p>
							<?php echo wp_kses_post(
								sprintf(
									__(
										'All optimizations made by %s are <strong>only visible to you</strong> and users who append <code>?omgf=1</code> to the URL. Disable Test Mode (at the bottom of this page) to make optimizations visible for everyone.',
										'host-webfonts-local'
									),
									apply_filters( 'omgf_settings_page_title', 'OMGF' )
								)
							); ?>
						</p>
					</div>
				</td>
			</tr>
		<?php endif; ?>
		<?php $nonce = wp_create_nonce( Settings::OMGF_ADMIN_PAGE ); ?>
		<tr id="task-manager-notice-row">
			<td colspan="2" class="task-manager-row">
				<?php
				$plugins                      = self::get_active_plugins();
				$warnings                     = self::get_warnings();
				$google_fonts_checker_results = $warnings[ 'google_fonts_checker' ] ?? [];

				if ( ! empty( $google_fonts_checker_results ) ) {
					unset( $warnings[ 'google_fonts_checker' ] );
				}
				?>
				<?php if ( ! empty( $google_fonts_checker_results ) ): ?>
					<div class="task-manager-notice <?php echo apply_filters( 'omgf_task_manager_notice_class', 'alert' ); ?>">
						<h4>
							<?php echo wp_kses_post(
								apply_filters(
									'omgf_google_fonts_checker_title',
									sprintf(
										__(
											'%1$s wasn\'t able to process all Google Fonts on your site. %2$s',
											'host-webfonts-local'
										),
										apply_filters( 'omgf_settings_page_title', 'OMGF' ),
										count( $google_fonts_checker_results ) === 5 ? '*' : ''
									)
								)
							); ?>
						</h4>
						<p>
							<?php echo wp_kses_post(
								apply_filters(
									'omgf_google_fonts_checker_general_text',
									sprintf(
										__(
											'Because either your theme, a plugin or a script has implemented Google Fonts in a way only rocket scientists use, %s isn\'t able to process all of them.',
											'host-webfonts-local'
										),
										apply_filters( 'omgf_settings_page_title', 'OMGF' )
									)
								)
							); ?>
						</p>
						<?php if ( empty( $warnings ) ): ?>
							<p>
								<?php echo apply_filters(
									'omgf_google_fonts_checker_no_potential_issues',
									sprintf(
										__(
											'You can read <a href="%s" target="_blank">this guide</a> and attempt to fix it manually or, <a href="%s" target="_blank">upgrade to OMGF Pro</a> to fix it automatically.',
											'host-webfonts-local'
										),
										'https://daan.dev/docs/omgf-pro-troubleshooting/external-requests/',
										Settings::DAAN_WORDPRESS_OMGF_PRO
									)
								); ?>
							</p>
						<?php else: ?>
							<p>
								<?php echo apply_filters(
									'omgf_google_fonts_checker_potential_issues',
									sprintf(
										__(
											'Some (or all) of the entries listed here might coincide with the list of potential issues listed below in the yellow box. Fix them first and visit the links below, to refresh these results. In some cases, an <a href="%s" target="_blank">upgrade to OMGF Pro</a> might be required.',
											'host-webfonts-local'
										),
										Settings::DAAN_WORDPRESS_OMGF_PRO
									)
								); ?>
							</p>
						<?php endif; ?>
						<ol>
							<?php foreach ( $google_fonts_checker_results as $url => $paths ) : ?>
								<li><strong><?php echo esc_html( $url ); ?></strong> <?php _e( 'was found on:', 'host-webfonts-local' ); ?></li>
								<ul>
									<?php foreach ( $paths as $path ) : ?>
										<li>
											<?php
											$href = OMGF::no_cache_optimize_url( $path );
											$path = $path === '/' ? '/ (home)' : $path;
											?>
											<a class="omgf-google-fonts-checker-result" href="<?php echo esc_attr( $href ); ?>" data-nonce="<?php echo esc_attr( $nonce ); ?>"><?php echo esc_html(
													$path
												); ?></a>
										</li>
									<?php endforeach; ?>
								</ul>
							<?php endforeach; ?>
						</ol>
						<?php if ( count( $google_fonts_checker_results ) === 5 ): ?>
							<sub>* <em><?php echo wp_kses_post(
										__( 'This list is limited to 5 pages, because most entries will most likely be duplicates.', 'host-webfonts-local' )
									); ?></em>
							</sub>
						<?php endif; ?>
					</div>
				<?php elseif ( empty( OMGF::admin_optimized_fonts() ) && ! OMGF::get_option( Settings::OMGF_OPTIMIZE_HAS_RUN ) ) : ?>
					<div class="task-manager-notice info">
						<h4><?php echo esc_html__( 'Let\'s get started!', 'host-webfonts-local' ); ?></h4>
						<p>
							<?php echo wp_kses_post(
								sprintf(
									__(
										'Hit the <strong>Save & Optimize</strong> at the bottom of this page to run a Google Fonts optimization on your homepage. After doing so, %s will silently run in the background and report back to you on this Dashboard if it encounters Google Fonts it can\'t detect and optimize automatically.',
										'host-webfonts-local'
									),
									apply_filters( 'omgf_settings_page_title', 'OMGF' )
								)
							); ?>
						</p>
					</div>
				<?php elseif ( empty( OMGF::admin_optimized_fonts() ) && OMGF::get_option( Settings::OMGF_OPTIMIZE_HAS_RUN ) ) : ?>
					<div class="task-manager-notice warning">
						<h4><?php echo esc_html__( 'Google Fonts optimization seems to be failing.', 'host-webfonts-local' ); ?></h4>
						<p>
							<?php echo wp_kses_post(
								sprintf(
									__(
										'%s isn\'t detecting any Google Fonts on your homepage. This could be for several reasons. <a href="%s" class="omgf-google-fonts-checker-result">Click here</a> to run a deeper investigation.',
										'host-webfonts-local'
									),
									apply_filters( 'omgf_settings_page_title', 'OMGF' ),
									OMGF::no_cache_optimize_url()
								)
							); ?>
						</p>
					</div>
				<?php else: ?>
					<div class="task-manager-notice success">
						<h4><?php echo esc_html__( 'No external Google Fonts found on your site.', 'host-webfonts-local' ); ?></h4>
						<p>
							<?php echo apply_filters(
								'omgf_dashboard_success_message',
								wp_kses_post(
									sprintf(
										__( 'Cool! %s is successfully hosting all Google Fonts locally.', 'host-webfonts-local' ),
										apply_filters( 'omgf_settings_page_title', 'OMGF' )
									)
								)
							); ?>
						</p>
						<?php do_action( 'omgf_dashboard_after_success_message' ); ?>
					</div>
				<?php endif; ?>
				<?php if ( empty( $warnings ) ) : ?>
					<div class="task-manager-notice success">
						<h4><?php echo esc_html__(
								'No potential issues found in your configuration.',
								'host-webfonts-local'
							); ?></h4>
						<p>
							<?php echo wp_kses_post(
								sprintf(
									__(
										'Great job! Your configuration allows %s to run smoothly.',
										'host-webfonts-local'
									),
									apply_filters( 'omgf_settings_page_title', 'OMGF' )
								)
							); ?>
						</p>
					</div>
				<?php else : ?>
					<div class="task-manager-notice warning">
						<h4><?php echo sprintf(
								esc_html(
									_n(
										'%s potential issue found in your configuration.',
										'%s potential issues found in your configuration.',
										count( $warnings ),
										'host-webfonts-local'
									)
								),
								count( $warnings )
							); ?>*</h4>
						<ol <?php echo count( $warnings ) === 1 ? "style='list-style: none; margin-left: 0;'" : ''; ?>>
							<?php foreach ( $warnings as $warning_id ) : ?>
								<?php $show_mark_as_fixed = true; ?>
								<li id="omgf-notice-<?php echo esc_attr( $warning_id ); ?>">
									<?php if ( $warning_id === 'is_multisite' ) : ?>
										<?php echo wp_kses_post(
											sprintf(
												__(
													'It seems like Multisite is enabled. OMGF doesn\'t natively support Multisite. If you\'re getting CORS related errors on any of your network\'s sites, consider <a href="%s" target="_blank">upgrading to OMGF Pro</a>.',
													'host-webfonts-local'
												),
												Settings::DAAN_WORDPRESS_OMGF_PRO
											)
										); ?>
									<?php endif; ?>
									<?php if ( $warning_id === 'no_ssl' ) : ?>
										<?php echo wp_kses_post(
											__(
												'Your WordPress configuration isn\'t setup to use SSL (https://). If your frontend is showing System Fonts after optimization, this might be due to Mixed-Content and/or CORS warnings. Follow <a href="https://daan.dev/docs/omgf-pro-troubleshooting/system-fonts/" target="_blank">these steps</a> to fix it.',
												'host-webfonts-local'
											)
										); ?>
									<?php endif; ?>
									<?php if ( in_array(
										str_replace( '-addtnl-conf', '', $warning_id ),
										self::THEMES_ADDTNL_CONF
									) ) : ?>
										<?php $template_id = str_replace(
											'-addtnl-conf',
											'',
											strtolower( $warning_id )
										); ?>
										<?php echo wp_kses_post(
											sprintf(
												__(
													'Your theme (%1$s) requires additional configuration to be compatible with %2$s, check the list of <a href="%3$s" target="_blank">known issues</a> to fix it.',
													'host-webfonts-local'
												),
												ucfirst( $template_id ),
												apply_filters( 'omgf_settings_page_title', 'OMGF' ),
												Settings::DAAN_DOCS_OMGF_PRO_KNOWN_ISSUES
											)
										); ?>
									<?php endif; ?>
									<?php if ( in_array(
										str_replace( '-incompatible', '', $warning_id ),
										self::INCOMPATIBLE_PLUGINS
									) ) : ?>
										<?php $plugin_name = $plugins[ str_replace(
											'-incompatible',
											'',
											$warning_id
										) ]; ?>
										<?php echo wp_kses_post(
											sprintf(
												__(
													'The plugin, <strong>%1$s</strong>, is incompatible with %2$s and needs to be disabled for %2$s to function properly. View the list of <a href="%3$s" target="_blank">known issues</a> for more information.',
													'host-webfonts-local'
												),
												$plugin_name,
												apply_filters( 'omgf_settings_page_title', 'OMGF' ),
												Settings::DAAN_DOCS_OMGF_PRO_KNOWN_ISSUES
											)
										); ?>
									<?php endif; ?>
									<?php if ( in_array(
										str_replace( '-addtnl-conf', '', $warning_id ),
										self::PLUGINS_ADDTNL_CONF
									) ) : ?>
										<?php $plugin_name = $plugins[ str_replace(
											'-addtnl-conf',
											'',
											$warning_id
										) ]; ?>
										<?php
										echo wp_kses_post(
											sprintf(
												__(
													'The plugin, <strong>%1$s</strong>, requires additional configuration to be compatible with %2$s. Check the <a href="%3$s" target="_blank">list of known issues</a> to fix it.',
													'host-webfonts-local'
												),
												$plugin_name,
												apply_filters( 'omgf_settings_page_title', 'OMGF' ),
												Settings::DAAN_DOCS_OMGF_PRO_KNOWN_ISSUES
											)
										);
										?>
									<?php endif; ?>
									<?php if ( in_array( $warning_id, array_keys( self::IFRAMES_LOADING_FONTS ) ) ) : ?>
										<?php $iframe_name = ucwords( str_replace( '-', ' ', $warning_id ) ); ?>
										<?php echo wp_kses_post(
											sprintf(
												__(
													'%1$s is loading an embedded iframe on your site. %2$s can\'t process Google Fonts inside iframes. <a href="%3$s" target="_blank">Click here</a> to find out why and what you can do about it.',
													'host-webfonts-local'
												),
												$iframe_name,
												apply_filters( 'omgf_settings_page_title', 'OMGF' ),
												'https://daan.dev/docs/omgf-pro-faq/iframes/'
											)
										); ?>
									<?php endif; ?>
									<?php if ( $show_mark_as_fixed ) : ?>
										<small>[<a href="#" class="hide-notice"
												   data-nonce="<?php echo esc_attr( $nonce ); ?>"
												   data-warning-id="<?php echo esc_attr( $warning_id ); ?>"
												   id="omgf-hide-notice-<?php echo esc_attr(
													   $warning_id
												   ); ?>"><?php echo esc_html__(
													'Mark as fixed',
													'host-webfonts-local'
												); ?></a>]</small>
									<?php endif; ?>
								</li>
							<?php endforeach; ?>
						</ol>
						<p>
							<sub>*<em><?php echo wp_kses_post(
										__(
											'After making the proposed changes where needed, click <strong>Mark as fixed</strong> to remove the notice. It won\'t disappear by itself.',
											'host-webfonts-local'
										)
									); ?></em></sub>
						</p>
					</div>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * @return array List of plugin names { (string) slug => (string) full name }
	 *
	 * @codeCoverageIgnore
	 */
	private static function get_active_plugins() {
		$plugins        = [];
		$active_plugins = array_intersect_key(
			get_plugins(),
			array_flip( array_filter( array_keys( get_plugins() ), 'is_plugin_active' ) )
		);

		foreach ( $active_plugins as $basename => $plugin ) {
			$slug = preg_replace( '/\/.*?\.php$/', '', $basename );

			$plugins[ $slug ] = $plugin[ 'Name' ];
		}

		return $plugins;
	}

	/**
	 * Check if WordPress setup has known issues.
	 *
	 * @return array
	 *
	 * @codeCoverageIgnore
	 */
	public static function get_warnings() {
		$warnings       = [];
		$hidden_notices = OMGF::get_option( Settings::OMGF_HIDDEN_NOTICES, [] );

		/**
		 * @since v5.5.4 Throw a warning if Multisite is enabled and OMGF Pro isn't installed/activated.
		 */
		if ( is_multisite() && ! class_exists( '\OMGF\Pro\Plugin' ) ) {
			$warnings[] = 'is_multisite';
		}

		/**
		 * @since v5.4.0 OMGF-50 Not using SSL on your site (or at least, not having it properly configured in WordPress) will cause OMGF to
		 *               add non-ssl (http://) links to stylesheets, and will lead to CORS and/or Mixed Content warnings in your frontend,
		 *               effectively showing nothing but system fonts.
		 */
		if ( str_contains( get_option( 'home' ), 'http://' ) || str_contains( get_option( 'siteurl' ), 'http://' ) ) {
			$warnings[] = 'no_ssl';
		}

		/**
		 * @since v5.4.0 OMGF-60 Warn the user if they're using a theme with known compatibility issues.
		 */
		$theme = wp_get_theme();

		if ( in_array( $theme->template, self::THEMES_ADDTNL_CONF ) ) {
			$warnings[] = $theme->template . '-addtnl-conf';
		}

		$plugins = self::get_active_plugins();
		$slugs   = array_keys( $plugins );

		/**
		 * @since v5.5.6 Notify users if they're using a plugin which is incompatible with OMGF (Pro)
		 */
		foreach ( self::INCOMPATIBLE_PLUGINS as $incompatible_plugin ) {
			if ( in_array( $incompatible_plugin, $slugs ) ) {
				$warnings[] = $incompatible_plugin . '-incompatible';
			}
		}

		/**
		 * @since v5.5.4 OMGF-74 Notify users if they're using a plugin which requires additional configuration due to known compatibility issues.
		 */
		foreach ( self::PLUGINS_ADDTNL_CONF as $plugin_addtnl_conf ) {
			if ( in_array( $plugin_addtnl_conf, $slugs ) ) {
				$warnings[] = $plugin_addtnl_conf . '-addtnl-conf';
			}
		}

		/**
		 * @since v5.4.0 OMGF-70 Notify users if they're loading scripts loading embedded iframes, e.g. Google Maps, Youtube, etc.
		 */
		$iframe_scripts = OMGF::get_option( Settings::OMGF_FOUND_IFRAMES, [] );

		foreach ( $iframe_scripts as $script_id ) {
			$warnings[] = $script_id; // @codeCoverageIgnore
		}

		$google_fonts_checker_results = OMGF::get_option( Settings::OMGF_GOOGLE_FONTS_CHECKER_RESULTS, [] );

		foreach ( $google_fonts_checker_results as $path => $found_urls ) {
			$warnings[ 'google_fonts_checker' ][ $path ] = $found_urls; // @codeCoverageIgnore
		}

		/**
		 * Process hidden warnings.
		 */
		foreach ( $warnings as $i => $warning ) {
			if ( in_array( $warning, $hidden_notices ) ) {
				unset( $warnings[ $i ] );
			}
		}

		return $warnings;
	}

	/**
	 * Render the status of cached stylesheets within the admin interface.
	 *
	 * This method displays the current cache status of optimized fonts and provides options to manage, configure, or remove stylesheets.
	 * It distinguishes between various statuses such as found, unloaded, stale, and not-found, and offers a legend for easy interpretation.
	 *
	 * @return void
	 *
	 * @codeCoverageIgnore because it's all frontend output.
	 */
	public static function render_status() {
		$stylesheets          = OMGF::admin_optimized_fonts();
		$unloaded_stylesheets = OMGF::unloaded_stylesheets();
		?>
		<tr>
			<th class="omgf-align-row-header" scope="row"><?php echo __( 'Cache Status', 'host-webfonts-local' ); ?></th>
			<td class="task-manager-row">
				<?php if ( ! empty( $stylesheets ) ) : ?>
					<ul>
						<?php foreach ( $stylesheets as $handle => $contents ) : ?>
							<?php
							$cache_key = OMGF::get_cache_key( $handle );

							if ( ! $cache_key ) {
								$cache_key = $handle;
							}

							$downloaded = file_exists( OMGF_UPLOAD_DIR . "/$cache_key/$cache_key.css" );
							$unloaded   = in_array( $handle, $unloaded_stylesheets );
							?>
							<li class="<?php echo OMGF_CACHE_IS_STALE ? 'stale' : ( $unloaded ? 'unloaded' : ( $downloaded ? 'found' : 'not-found' ) ); ?>">
								<strong><?php echo $handle; ?></strong> <em>(<?php echo sprintf(
										__( 'stored in %s', 'host-webfonts-local' ),
										str_replace( ABSPATH, '', OMGF_UPLOAD_DIR . "/$cache_key" )
									); ?>)</em>
								<?php
								if ( ! $unloaded ) :
									?>
									<a href="<?php echo $downloaded ? "#$handle" : '#'; ?>"
									   data-handle="<?php echo esc_attr( $handle ); ?>"
									   class="<?php echo $downloaded ? 'omgf-manage-stylesheet' : 'omgf-remove-stylesheet'; ?>"
									   title="<?php echo sprintf(
										   __( 'Manage %s', 'host-webfonts-local' ),
										   $cache_key
									   ); ?>"><?php $downloaded ? _e( 'Configure', 'host-webfonts-local' ) : _e( 'Remove', 'host-webfonts-local' ); ?></a><?php endif; ?>
							</li>
						<?php endforeach; ?>
						<?php if ( OMGF_CACHE_IS_STALE ) : ?>
							<li class="stale-cache-notice"><em><?php echo wp_kses_post(
										__(
											'The stylesheets in the cache do not reflect the current settings. Either <a href="#" id="omgf-cache-refresh">refresh</a> the cache (and maintain settings) or <a href="#" id="omgf-cache-flush">flush</a> it and start over.',
											'host-webfonts-local'
										)
									); ?></em></li>
						<?php endif; ?>
					</ul>
				<?php else : ?>
					<p>
						<?php echo __( 'No stylesheets in cache.', 'host-webfonts-local' ); ?>
					</p>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<th scope="row"></th>
			<td class="task-manager-row omgf-cache-legend">
				<p><strong><?php _e( 'Status Legend', 'host-webfonts-local' ); ?></strong></p>
				<ul>
					<li class="omgf-cache-legend-item found"> <?php echo wp_kses_post(
							__(
								'<span class="omgf-cache-legend-item-title">Found</span> Stylesheet exists on your file system.',
								'host-webfonts-local'
							)
						); ?></li>
					<li class="omgf-cache-legend-item unloaded"> <?php echo wp_kses_post(
							__(
								'<span class="omgf-cache-legend-item-title">Unloaded</span> Stylesheet exists but is not loaded in the frontend.',
								'host-webfonts-local'
							)
						); ?></li>
					<li class="omgf-cache-legend-item stale"> <?php echo wp_kses_post(
							__(
								'<span class="omgf-cache-legend-item-title">Stale</span> Settings were changed and the stylesheet\'s content does not reflect those changes.',
								'host-webfonts-local'
							)
						); ?></li>
					<li class="omgf-cache-legend-item not-found"> <?php echo wp_kses_post(
							__(
								"<span class='omgf-cache-legend-item-title'>Pending / Not Found</span> Any changes you made to this stylesheet will be processed the next time the page it was found on is requested. If you didn't make any changes, it's probably orphaned and it's safe to remove it.",
								'host-webfonts-local'
							)
						); ?></li>
				</ul>
			</td>
		</tr>
		<?php
	}
}
