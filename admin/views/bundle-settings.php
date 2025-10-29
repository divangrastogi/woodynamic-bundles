<?php
/**
 * Bundle Settings Page Template
 *
 * @package WooDynamic\Bundles
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check permissions
if (!current_user_can('manage_woocommerce')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}
?>

<div class="wrap">
    <h1><?php _e('WooDynamic Bundles Settings', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></h1>

    <?php settings_errors(); ?>

    <form method="post" action="">
        <?php wp_nonce_field('woodynamic_settings_save', 'woodynamic_settings_nonce'); ?>

        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Enable Bundles', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="woodynamic_enable_bundles" value="yes" <?php checked(get_option('woodynamic_enable_bundles', 'yes'), 'yes'); ?>>
                        <?php _e('Enable bundle functionality', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php _e('Default Layout', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></th>
                <td>
                    <select name="woodynamic_default_layout">
                        <option value="grid" <?php selected(get_option('woodynamic_default_layout', 'grid'), 'grid'); ?>><?php _e('Grid', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></option>
                        <option value="list" <?php selected(get_option('woodynamic_default_layout', 'list'), 'list'); ?>><?php _e('List', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></option>
                    </select>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php _e('Maximum Saved Bundles', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="number" name="woodynamic_max_saved_bundles" value="<?php echo esc_attr(get_option('woodynamic_max_saved_bundles', 5)); ?>" min="1" max="50">
                    <p class="description"><?php _e('Maximum number of bundles a user can save.', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php _e('Allow Guest Users to Save', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="woodynamic_allow_guest_save" value="yes" <?php checked(get_option('woodynamic_allow_guest_save', 'no'), 'yes'); ?>>
                        <?php _e('Allow guest users to save bundles', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php _e('Display Settings', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="woodynamic_show_bundle_badge" value="yes" <?php checked(get_option('woodynamic_show_bundle_badge', 'yes'), 'yes'); ?>>
                        <?php _e('Show bundle badge on products', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?>
                    </label><br>

                    <label>
                        <input type="checkbox" name="woodynamic_show_savings" value="yes" <?php checked(get_option('woodynamic_show_savings', 'yes'), 'yes'); ?>>
                        <?php _e('Show savings percentage', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?>
                    </label><br>

                    <label>
                        <input type="checkbox" name="woodynamic_show_individual_prices" value="yes" <?php checked(get_option('woodynamic_show_individual_prices', 'yes'), 'yes'); ?>>
                        <?php _e('Show individual product prices', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php _e('Cart Settings', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="woodynamic_group_cart_items" value="yes" <?php checked(get_option('woodynamic_group_cart_items', 'yes'), 'yes'); ?>>
                        <?php _e('Group bundled items in cart', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?>
                    </label><br>

                    <label>
                        <input type="checkbox" name="woodynamic_show_bundle_name_cart" value="yes" <?php checked(get_option('woodynamic_show_bundle_name_cart', 'yes'), 'yes'); ?>>
                        <?php _e('Show bundle name in cart', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php _e('Performance Settings', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="woodynamic_enable_caching" value="yes" <?php checked(get_option('woodynamic_enable_caching', 'yes'), 'yes'); ?>>
                        <?php _e('Enable caching', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?>
                    </label><br>

                    <label>
                        <?php _e('Cache Duration (seconds)', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?>:
                        <input type="number" name="woodynamic_cache_duration" value="<?php echo esc_attr(get_option('woodynamic_cache_duration', 3600)); ?>" min="300" max="86400">
                    </label>
                </td>
            </tr>
        </table>

        <?php submit_button(__('Save Settings', WOODYNAMIC_BUNDLES_TEXT_DOMAIN)); ?>
    </form>

    <div class="woodynamic-settings-info">
        <h2><?php _e('Getting Started', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></h2>
        <ol>
            <li><?php _e('Create a new Bundle Template from the WooCommerce menu.', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></li>
            <li><?php _e('Configure bundle rules, pricing, and product selection.', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></li>
            <li><?php _e('Use the shortcode [woodynamic_bundle id="123"] to display the bundle on any page.', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></li>
            <li><?php _e('Test the bundle functionality in your store.', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></li>
        </ol>

        <h3><?php _e('Shortcode Usage', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></h3>
        <code>[woodynamic_bundle id="123" layout="grid" columns="3"]</code>

        <h3><?php _e('Available Attributes', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></h3>
        <ul>
            <li><strong>id</strong> (required): Bundle template ID</li>
            <li><strong>layout</strong>: 'grid' or 'list' (default: grid)</li>
            <li><strong>columns</strong>: Number of columns (default: 3)</li>
        </ul>
    </div>
</div>

<style>
.woodynamic-settings-info {
    margin-top: 30px;
    padding: 20px;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}

.woodynamic-settings-info h2,
.woodynamic-settings-info h3 {
    color: #23282d;
}

.woodynamic-settings-info code {
    background: #f1f1f1;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: monospace;
}

.woodynamic-settings-info ul {
    margin-left: 20px;
}
</style>
