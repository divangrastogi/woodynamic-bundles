<?php
/**
 * WooDynamic Bundles Uninstall
 *
 * Fired when the plugin is uninstalled.
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check if user wants to keep data
$keep_data = get_option('woodynamic_keep_data_on_uninstall', false);

if (!$keep_data) {
    global $wpdb;

    // Delete custom tables
    $table_name = $wpdb->prefix . 'woodynamic_saved_bundles';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");

    // Delete bundle templates (custom post type)
    $bundle_posts = get_posts(array(
        'post_type' => 'wc_bundle_template',
        'posts_per_page' => -1,
        'post_status' => 'any',
    ));

    foreach ($bundle_posts as $post) {
        wp_delete_post($post->ID, true);
    }

    // Delete options
    $options_to_delete = array(
        'woodynamic_bundles_version',
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
        'woodynamic_db_version',
        'woodynamic_bundles_activated',
        'woodynamic_keep_data_on_uninstall',
    );

    foreach ($options_to_delete as $option) {
        delete_option($option);
    }

    // Clear any transients
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

    // Clear scheduled events if any
    wp_clear_scheduled_hook('woodynamic_daily_maintenance');
}
