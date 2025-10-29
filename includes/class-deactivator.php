<?php
/**
 * Deactivator class for WooDynamic Bundles
 *
 * Handles plugin deactivation tasks
 *
 * @package WooDynamic\Bundles
 */

namespace WooDynamic\Bundles;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Deactivator class
 */
class Deactivator {

    /**
     * Plugin deactivation hook
     */
    public static function woodynamic_deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();

        // Clear any cached data
        self::woodynamic_clear_cache();

        // Trigger deactivation action
        do_action('woodynamic_bundles_deactivated');
    }

    /**
     * Clear plugin cache and transients
     */
    private static function woodynamic_clear_cache() {
        global $wpdb;

        // Clear transients
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_woodynamic_%'
            )
        );

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_timeout_woodynamic_%'
            )
        );

        // Clear object cache if available
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
    }
}
