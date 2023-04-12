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

use OMGF\Admin\Settings;

defined( 'ABSPATH' ) || exit;

class Builder {

	/** @var string $plugin_text_domain */
	protected $plugin_text_domain = 'host-webfonts-local';

	/** @var $title */
	protected $title;

	/** @var $promo string */
	protected $promo;

	/** @var array Stores all allow HTML elements for escaping. */
	protected $allowed_html;

	/**
	 * Only sets the promo string on settings load.
	 *
	 * Settings_Builder constructor.
	 */
	public function __construct() {
		global $allowedposttags;

		$this->allowed_html = $allowedposttags;

		add_filter( 'omgf_optimize_settings_content', [ $this, 'do_promo' ] );
		add_filter( 'omgf_detection_settings_content', [ $this, 'do_promo' ] );
		add_filter( 'omgf_advanced_settings_content', [ $this, 'do_promo' ] );
	}

	/**
	 *
	 */
	public function do_promo() {
		if ( apply_filters( 'apply_omgf_pro_promo', true ) ) {
			$this->promo = apply_filters( 'omgf_pro_promo', sprintf( __( '<a href="%s" target="_blank">Upgrade to Pro</a> to unlock this option.', 'host-webfonts-local' ), Settings::DAAN_WORDPRESS_OMGF_PRO ) );
		}
	}

	/**
	 *
	 */
	public function do_before() {       ?>
		<table class="form-table">
		<?php
	}

	/**
	 *
	 */
	public function do_after() {
		?>
		</table>
		<?php
	}

	/**
	 *
	 */
	public function do_title() {
		?>
		<h3><?php echo esc_html( $this->title ); ?></h3>
		<?php
	}

	/**
	 * Generate radio setting
	 *
	 * @param $label
	 * @param $inputs
	 * @param $name
	 * @param $checked
	 * @param $description
	 */
	public function do_radio( $label, $inputs, $name, $checked, $description ) {
		?>
		<tr>
			<th scope="row"><?php echo esc_html( $label ); ?></th>
			<td>
				<?php foreach ( $inputs as $option => $option_label ) : ?>
					<label>
						<input type="radio" <?php echo esc_attr( strpos( $option_label, '(Pro)' ) !== false ? apply_filters( $name . '_' . $option . '_setting_disabled', 'disabled' ) : '' ); ?> class="<?php echo esc_attr( str_replace( '_', '-', $name . '_' . $option ) ); ?>" name="omgf_settings[<?php echo esc_attr( $name ); ?>]" value="<?php echo esc_attr( $option ); ?>" <?php echo esc_attr( $option === $checked ? 'checked="checked"' : '' ); ?> />
						<?php echo esc_html( $option_label ); ?>
					</label>
					<br />
				<?php endforeach; ?>
				<p class="description">
					<?php echo wp_kses( $description . ' ' . $this->promo, $this->allowed_html ); ?>
				</p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Generate select setting
	 *
	 * @param      $label
	 * @param      $name
	 * @param      $options
	 * @param      $selected
	 * @param      $description
	 * @param bool $update_required
	 */
	public function do_select( $label, $name, $options, $selected, $description, $is_multiselect = false, $disabled = false ) {
		?>
		<tr>
			<th scope="row">
				<?php echo esc_html( apply_filters( $name . '_setting_label', $label ) ); ?>
			</th>
			<td>
				<select name="omgf_settings[<?php echo esc_attr( $name ); ?>]<?php echo esc_attr( $is_multiselect ? '[]' : '' ); ?>" class="<?php echo esc_attr( str_replace( '_', '-', $name ) ); ?>" <?php echo $is_multiselect ? 'size="6" multiple="multiple"' : ''; ?> <?php echo apply_filters( $name . '_setting_disabled', $disabled ) ? 'disabled' : ''; ?>>
					<?php
					$options = apply_filters( $name . '_setting_options', $options );
					?>
					<?php foreach ( $options as $option => $option_label ) : ?>
						<?php
						if ( is_array( $selected ) ) {
							$is_selected = in_array( $option, $selected );
						} else {
							$is_selected = $selected === $option;
						}
						?>
						<option value="<?php echo esc_attr( $option ); ?>" <?php echo esc_attr( $is_selected ? 'selected="selected"' : '' ); ?>><?php echo wp_kses( $option_label, $this->allowed_html ); ?></option>
					<?php endforeach; ?>
				</select>
				<p class="description">
					<?php echo wp_kses( apply_filters( $name . '_setting_description', $description ), $this->allowed_html ); ?>
				</p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Generate number setting.
	 *
	 * @param $label
	 * @param $name
	 * @param $value
	 * @param $description
	 */
	public function do_number( $label, $name, $value, $description, $min = 0, $visible = true ) {
		?>
		<tr valign="top" <?php echo $visible ? '' : 'style="display: none;"'; ?>>
			<th scope="row"><?php echo esc_html( apply_filters( $name . '_setting_label', $label ) ); ?></th>
			<td>
				<input class="<?php echo esc_attr( str_replace( '_', '-', $name ) ); ?>" type="number" name="omgf_settings[<?php echo esc_attr( $name ); ?>]" min="<?php echo esc_attr( $min ); ?>" value="<?php echo esc_attr( $value ); ?>" />
				<p class="description">
					<?php echo wp_kses( apply_filters( $name . '_setting_description', $description ), $this->allowed_html ); ?>
				</p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Generate text setting.
	 *
	 * @param        $label
	 * @param        $name
	 * @param        $placeholder
	 * @param        $value
	 * @param string $description
	 * @param bool   $update_required
	 */
	public function do_text( $label, $name, $placeholder, $value, $description = '', $disabled = false ) {
		?>
		<tr class="<?php echo esc_attr( str_replace( '_', '-', $name ) ); ?>-row">
			<th scope="row"><?php echo esc_html( apply_filters( $name . '_setting_label', $label ) ); ?></th>
			<td>
				<input <?php echo apply_filters( $name . '_setting_disabled', $disabled ) ? 'disabled' : ''; ?> class="<?php echo esc_attr( str_replace( '_', '-', $name ) ); ?>" type="text" name="omgf_settings[<?php echo esc_attr( $name ); ?>]" placeholder="<?php echo esc_attr( $placeholder ); ?>" value="<?php echo esc_attr( $value ); ?>" />
				<p class="description">
					<?php echo wp_kses( apply_filters( $name . 'setting_description', $description ), $this->allowed_html ); ?>
				</p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Generate checkbox setting.
	 *
	 * @param $label
	 * @param $name
	 * @param $checked
	 * @param $description
	 */
	public function do_checkbox( $label, $name, $checked, $description, $disabled = false, $td_classes = '' ) {
		?>
		<tr>
			<th scope="row"><?php echo esc_attr( apply_filters( $name . '_setting_label', $label ) ); ?></th>
			<td <?php echo esc_attr( $td_classes ? "class=$td_classes" : '' ); ?>>
				<label for="<?php echo esc_attr( $name ); ?>">
					<?php if ( ! $disabled ) : ?>
						<input type="hidden" name="omgf_settings[<?php echo esc_attr( $name ); ?>]" value="0" />
					<?php endif; ?>
					<input id="<?php echo esc_attr( $name ); ?>" type="checkbox" <?php echo apply_filters( $name . '_setting_disabled', $disabled ) ? 'disabled' : ''; ?> class="<?php echo esc_attr( str_replace( '_', '-', $name ) ); ?>" name="omgf_settings[<?php echo esc_attr( $name ); ?>]" <?php echo esc_attr( $checked ? 'checked = "checked"' : '' ); ?> value="on" />
					<?php echo wp_kses( apply_filters( $name . '_setting_description', $description ), $this->allowed_html ); ?>
				</label>
			</td>
		</tr>
		<?php
	}
}
