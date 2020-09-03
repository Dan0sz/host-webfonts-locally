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

defined('ABSPATH') || exit;

class OMGF_Admin_Settings_Builder
{
    const WOOSH_WORDPRESS_PLUGINS_HOST_GOOGLE_FONTS_PRO = 'https://ffwp.dev/wordpress-plugins/host-google-fonts-pro/#get-omgf-pro';

    /** @var string $plugin_text_domain */
    protected $plugin_text_domain = 'host-webfonts-local';

    /** @var $title */
    protected $title;

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
     * Generate select setting
     *
     * @param      $label
     * @param      $select
     * @param      $options
     * @param      $selected
     * @param      $description
     * @param bool $update_required
     */
    public function do_select($label, $select, $options, $selected, $description, $update_required = false)
    {
        ?>
        <tr>
            <th scope="row">
                <?= apply_filters($select . '_setting_label', $label); ?> <?= $update_required ?: ''; ?>
            </th>
            <td>
                <select name="<?= $select; ?>" class="<?= str_replace('_', '-', $select); ?>">
                    <?php
                    $options = apply_filters($select . '_setting_options', $options);
                    ?>
                    <?php foreach ($options as $option => $option_label): ?>
                        <option value="<?= $option; ?>" <?= ($selected == $option) ? 'selected' : ''; ?>><?= $option_label; ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="description">
                    <?= apply_filters($select . '_setting_description', $description); ?>
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
                <input class="<?= str_replace('_', '-', $name); ?>" type="number" name="<?= $name; ?>" min="<?= $min; ?>" value="<?= $value; ?>"/>
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
    public function do_text($label, $name, $placeholder, $value, $description = '', $update_required = false, $visible = true)
    {
        ?>
        <tr class="<?= str_replace('_', '-', $name); ?>-row" <?= $visible ? '' : 'style="display: none;"'; ?>>
            <th scope="row"><?= apply_filters($name . '_setting_label', $label); ?> <?= $update_required ?: ''; ?></th>
            <td>
                <input class="<?= str_replace('_', '-', $name); ?>" type="text" name="<?= $name; ?>" placeholder="<?= $placeholder; ?>" value="<?= $value; ?>"/>
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
    public function do_checkbox($label, $name, $checked, $description, $update_required = false, $disabled = false)
    {
        ?>
        <tr>
            <th scope="row"><?= apply_filters($name . '_setting_label', $label); ?> <?= $update_required ?: ''; ?></th>
            <td>
                <input type="checkbox" <?= apply_filters($name . '_setting_disabled', $disabled) ? 'disabled' : ''; ?> class="<?= str_replace('_' , '-' , $name); ?>" name="<?= $name; ?>"
                    <?= $checked == "on" ? 'checked = "checked"' : ''; ?> />
                <p class="description">
                    <?= apply_filters($name . '_setting_description', $description); ?>
                </p>
            </td>
        </tr>
        <?php
    }
}
