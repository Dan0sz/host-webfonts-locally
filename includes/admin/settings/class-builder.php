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
 * @copyright: (c) 2021 Daan van den Bergh
 * @url      : https://daan.dev
 * * * * * * * * * * * * * * * * * * * */

defined('ABSPATH') || exit;

class OMGF_Admin_Settings_Builder
{
	/** @var string $plugin_text_domain */
	protected $plugin_text_domain = 'host-webfonts-local';

	/** @var $title */
	protected $title;

	/** @var $promo string */
	protected $promo;

	/**
	 * Only sets the promo string on settings load.
	 *
	 * OMGF_Admin_Settings_Builder constructor.
	 */
	public function __construct()
	{
		add_filter('omgf_optimize_settings_content', [$this, 'do_promo']);
		add_filter('omgf_detection_settings_content', [$this, 'do_promo']);
		add_filter('omgf_advanced_settings_content', [$this, 'do_promo']);
	}

	/**
	 *
	 */
	public function do_promo()
	{
		if (apply_filters('apply_omgf_pro_promo', true)) {
			$this->promo = apply_filters('omgf_pro_promo', sprintf(__('<a href="%s" target="_blank">Upgrade to Pro</a> to enable this option.', $this->plugin_text_domain), OMGF_Admin_Settings::FFWP_WORDPRESS_PLUGINS_OMGF_PRO));
		}
	}

	/**
	 *
	 */
	public function do_before()
	{
?>
		<table class="form-table">
		<?php
	}

	/**
	 *
	 */
	public function do_after()
	{
		?>
		</table>
	<?php
	}

	/**
	 *
	 */
	public function do_title()
	{
	?>
		<h3><?= $this->title ?></h3>
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
	public function do_radio($label, $inputs, $name, $checked, $description)
	{
	?>
		<tr>
			<th scope="row"><?= $label; ?></th>
			<td>
				<?php foreach ($inputs as $option => $option_label) : ?>
					<label>
						<input type="radio" class="<?= str_replace('_', '-', $name . '_' . $option); ?>" name="<?= $name; ?>" value="<?= $option; ?>" <?= $option == $checked ? 'checked="checked"' : ''; ?> />
						<?= $option_label; ?>
					</label>
					<br />
				<?php endforeach; ?>
				<p class="description">
					<?= $description; ?>
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
	public function do_select($label, $name, $options, $selected, $description, $is_multiselect = false, $disabled = false)
	{
	?>
		<tr>
			<th scope="row">
				<?= apply_filters($name . '_setting_label', $label); ?>
			</th>
			<td>
				<select name="<?= $name; ?><?= $is_multiselect ? '[]' : ''; ?>" class="<?= str_replace('_', '-', $name); ?>" <?= $is_multiselect ? 'size="8" multiple="multiple"' : ''; ?> <?= apply_filters($name . '_setting_disabled', $disabled) ? 'disabled' : ''; ?>>
					<?php
					$options = apply_filters($name . '_setting_options', $options);
					?>
					<?php foreach ($options as $option => $option_label) : ?>
						<?php
						if (is_array($selected)) {
							$is_selected = in_array($option, $selected);
						} else {
							$is_selected = $selected == $option;
						}
						?>
						<option value="<?= $option; ?>" <?= $is_selected ? 'selected="selected"' : ''; ?>><?= $option_label; ?></option>
					<?php endforeach; ?>
				</select>
				<p class="description">
					<?= apply_filters($name . '_setting_description', $description); ?>
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
	public function do_number($label, $name, $value, $description, $min = 0)
	{
	?>
		<tr valign="top">
			<th scope="row"><?= apply_filters($name . '_setting_label', $label); ?></th>
			<td>
				<input class="<?= str_replace('_', '-', $name); ?>" type="number" name="<?= $name; ?>" min="<?= $min; ?>" value="<?= $value; ?>" />
				<p class="description">
					<?= apply_filters($name . '_setting_description', $description); ?>
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
	public function do_text($label, $name, $placeholder, $value, $description = '', $disabled = false)
	{
	?>
		<tr class="<?= str_replace('_', '-', $name); ?>-row">
			<th scope="row"><?= apply_filters($name . '_setting_label', $label); ?></th>
			<td>
				<input <?= apply_filters($name . '_setting_disabled', $disabled) ? 'disabled' : ''; ?> class="<?= str_replace('_', '-', $name); ?>" type="text" name="<?= $name; ?>" placeholder="<?= $placeholder; ?>" value="<?= $value; ?>" />
				<p class="description">
					<?= apply_filters($name . 'setting_description', $description); ?>
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
	public function do_checkbox($label, $name, $checked, $description, $disabled = false)
	{
	?>
		<tr>
			<th scope="row"><?= apply_filters($name . '_setting_label', $label); ?></th>
			<td>
				<label for="<?= $name; ?>">
					<input id="<?= $name; ?>" type="checkbox" <?= apply_filters($name . '_setting_disabled', $disabled) ? 'disabled' : ''; ?> class="<?= str_replace('_', '-', $name); ?>" name="<?= $name; ?>" <?= $checked == "on" ? 'checked = "checked"' : ''; ?> />
					<?= apply_filters($name . '_setting_description', $description); ?>
				</label>
			</td>
		</tr>
<?php
	}
}
