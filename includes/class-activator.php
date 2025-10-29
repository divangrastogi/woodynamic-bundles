<?php
/**
 * Activator class for WooDynamic Bundles
 *
 * Handles plugin activation tasks
 *
 * @package WooDynamic\Bundles
 */

namespace WooDynamic\Bundles;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Activator class
 */
class Activator {

    /**
     * Plugin activation hook
     */
    public static function woodynamic_activate() {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            deactivate_plugins(plugin_basename(WOODYNAMIC_BUNDLES_PLUGIN_FILE));
            wp_die('WooDynamic Bundles requires WooCommerce to be installed and active.');
        }

        // Check WordPress version
        if (version_compare(get_bloginfo('version'), '6.0', '<')) {
            deactivate_plugins(plugin_basename(WOODYNAMIC_BUNDLES_PLUGIN_FILE));
            wp_die('WooDynamic Bundles requires WordPress 6.0 or higher.');
        }

        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            deactivate_plugins(plugin_basename(WOODYNAMIC_BUNDLES_PLUGIN_FILE));
            wp_die('WooDynamic Bundles requires PHP 7.4 or higher.');
        }

        // Create custom database tables
        self::woodynamic_create_tables();

        // Set default options
        self::woodynamic_set_default_options();

        // Set activation flag
        add_option('woodynamic_bundles_activated', time());

        // Trigger activation action
        do_action('woodynamic_bundles_activated');
    }

    /**
     * Create custom database tables
     */
    private static function woodynamic_create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Saved bundles table
        $table_name = $wpdb->prefix . 'woodynamic_saved_bundles';

        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            bundle_template_id BIGINT(20) UNSIGNED NOT NULL,
            bundle_name VARCHAR(255) DEFAULT '',
            bundle_data LONGTEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX user_id_idx (user_id),
            INDEX bundle_template_id_idx (bundle_template_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        // Store table version
        add_option('woodynamic_bundles_db_version', WOODYNAMIC_BUNDLES_VERSION);
    }

    /**
     * Set default plugin options
     */
    private static function woodynamic_set_default_options() {
        $defaults = array(
            'woodynamic_bundles_version' => WOODYNAMIC_BUNDLES_VERSION,
            'woodynamic_enable_bundles' => 'yes',
            'woodynamic_default_layout' => 'grid',
            'woodynamic_max_saved_bundles' => 5,
            'woodynamic_allow_guest_save' => 'no',
            'woodynamic_show_bundle_badge' => 'yes',
            'woodynamic_bundle_badge_text' => 'Bundle',
            'woodynamic_show_savings' => 'yes',
            'woodynamic_show_individual_prices' => 'yes',
            'woodynamic_group_cart_items' => 'yes',
            'woodynamic_allow_remove_individual' => 'no',
            'woodynamic_show_bundle_name_cart' => 'yes',
            'woodynamic_discount_before_tax' => 'yes',
            'woodynamic_enable_caching' => 'yes',
            'woodynamic_cache_duration' => 3600, // 1 hour
            'woodynamic_preload_data' => 'no',
        );

        foreach ($defaults as $option => $value) {
            if (!get_option($option)) {
                add_option($option, $value);
            }
        }
    }
}
