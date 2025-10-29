<?php
/**
 * Admin Menu Class
 *
 * Handles admin menu registration and assets
 *
 * @package WooDynamic\Bundles
 */

namespace WooDynamic\Bundles;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin_Menu class
 */
class Admin_Menu {

    /**
     * Plugin name
     *
     * @var string
     */
    private $plugin_name;

    /**
     * Plugin version
     *
     * @var string
     */
    private $version;

    /**
     * Initialize the class
     *
     * @param string $plugin_name
     * @param string $version
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Add admin menus
     */
    public function woodynamic_add_menu_pages() {
        // Add Bundle Templates submenu under WooCommerce
        add_submenu_page(
            'woocommerce',
            __('Bundle Templates', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            __('Bundle Templates', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            'manage_woocommerce',
            'edit.php?post_type=wc_bundle_template'
        );

        // Add Bundle Settings submenu
        add_submenu_page(
            'woocommerce',
            __('Bundle Settings', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            __('Bundle Settings', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            'manage_woocommerce',
            'woodynamic-bundle-settings',
            array($this, 'woodynamic_settings_page')
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function woodynamic_enqueue_scripts() {
        $screen = get_current_screen();

        // Only load on our pages
        if (!$this->woodynamic_is_our_admin_page($screen)) {
            return;
        }

        // Enqueue styles
        wp_enqueue_style(
            $this->plugin_name . '-admin',
            WOODYNAMIC_BUNDLES_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            $this->version,
            'all'
        );

        // Enqueue scripts
        wp_enqueue_script(
            $this->plugin_name . '-admin',
            WOODYNAMIC_BUNDLES_PLUGIN_URL . 'assets/js/admin-bundle-builder.js',
            array('jquery', 'jquery-ui-sortable', 'wp-api'),
            $this->version,
            false
        );

        // Localize script
        wp_localize_script(
            $this->plugin_name . '-admin',
            'woodynamic_admin',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('woodynamic_admin_nonce'),
                'strings' => array(
                    'confirm_delete' => __('Are you sure you want to delete this bundle template?', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
                    'no_products_found' => __('No products found.', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
                    'searching' => __('Searching...', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
                    'select_products' => __('Select Products', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
                    'add_tier' => __('Add Tier', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
                    'remove_tier' => __('Remove', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
                ),
            )
        );

        // Enqueue media for featured images
        if ($screen->post_type === 'wc_bundle_template') {
            wp_enqueue_media();
        }
    }

    /**
     * Check if current admin page is one of our pages
     *
     * @param WP_Screen $screen
     * @return bool
     */
    private function woodynamic_is_our_admin_page($screen) {
        if (!$screen) {
            return false;
        }

        // Bundle templates list and edit pages
        if ($screen->post_type === 'wc_bundle_template') {
            return true;
        }

        // Settings page
        if ($screen->id === 'woocommerce_page_woodynamic-bundle-settings') {
            return true;
        }

        return false;
    }

    /**
     * Render settings page
     */
    public function woodynamic_settings_page() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // Save settings if form submitted
        if (isset($_POST['woodynamic_settings_nonce']) && wp_verify_nonce($_POST['woodynamic_settings_nonce'], 'woodynamic_settings_save')) {
            $this->woodynamic_save_settings();
            echo '<div class="notice notice-success"><p>' . __('Settings saved successfully.', WOODYNAMIC_BUNDLES_TEXT_DOMAIN) . '</p></div>';
        }

        // Include settings page template
        if (file_exists(WOODYNAMIC_BUNDLES_PLUGIN_DIR . 'admin/views/bundle-settings.php')) {
            include WOODYNAMIC_BUNDLES_PLUGIN_DIR . 'admin/views/bundle-settings.php';
        } else {
            $this->woodynamic_render_settings_page();
        }
    }

    /**
     * Save settings
     */
    private function woodynamic_save_settings() {
        $settings = array(
            'woodynamic_enable_bundles',
            'woodynamic_default_layout',
            'woodynamic_max_saved_bundles',
            'woodynamic_allow_guest_save',
            'woodynamic_show_bundle_badge',
            'woodynamic_bundle_badge_text',
            'woodynamic_show_savings',
            'woodynamic_show_individual_prices',
            'woodynamic_group_cart_items',
            'woodynamic_allow_remove_individual',
            'woodynamic_show_bundle_name_cart',
            'woodynamic_discount_before_tax',
            'woodynamic_enable_caching',
            'woodynamic_cache_duration',
            'woodynamic_preload_data',
        );

        foreach ($settings as $setting) {
            if (isset($_POST[$setting])) {
                $value = sanitize_text_field($_POST[$setting]);
                update_option($setting, $value);
            }
        }
    }

    /**
     * Render settings page HTML
     */
    private function woodynamic_render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('WooDynamic Bundles Settings', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></h1>

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
        </div>
        <?php
    }

    /**
     * Add bundle count to admin menu
     */
    public function woodynamic_add_menu_bubble() {
        global $menu;

        $count = wp_count_posts('wc_bundle_template');
        $pending_count = isset($count->draft) ? $count->draft : 0;

        if ($pending_count > 0) {
            foreach ($menu as $key => $menu_item) {
                if (isset($menu_item[2]) && $menu_item[2] === 'woocommerce') {
                    $menu[$key][0] .= ' <span class="awaiting-mod">' . $pending_count . '</span>';
                    break;
                }
            }
        }
    }
}
