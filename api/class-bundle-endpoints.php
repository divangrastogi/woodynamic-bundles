<?php
/**
 * Bundle Endpoints Class
 *
 * Additional API endpoints for bundles
 *
 * @package WooDynamic\Bundles
 */

namespace WooDynamic\Bundles;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bundle_Endpoints class
 */
class Bundle_Endpoints {

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
     * Register additional endpoints
     */
    public function woodynamic_register_endpoints() {
        // This class can be used for additional endpoints beyond the main REST controller
        // For now, it's a placeholder for future expansion
    }

    /**
     * Get bundle statistics
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function woodynamic_get_bundle_stats($request) {
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            return new WP_Error('insufficient_permissions', __('Insufficient permissions', WOODYNAMIC_BUNDLES_TEXT_DOMAIN), array('status' => 403));
        }

        $stats = array(
            'total_bundles' => wp_count_posts('wc_bundle_template')->publish,
            'active_bundles' => $this->woodynamic_count_active_bundles(),
            'total_sales' => $this->woodynamic_get_bundle_sales(),
        );

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $stats,
        ), 200);
    }

    /**
     * Count active bundles
     *
     * @return int
     */
    private function woodynamic_count_active_bundles() {
        $args = array(
            'post_type' => 'wc_bundle_template',
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_bundle_status',
                    'value' => 'active',
                    'compare' => '=',
                ),
            ),
            'posts_per_page' => -1,
        );

        $query = new WP_Query($args);
        return $query->found_posts;
    }

    /**
     * Get bundle sales statistics
     *
     * @return array
     */
    private function woodynamic_get_bundle_sales() {
        global $wpdb;

        // This would require more complex queries to track bundle sales
        // For now, return placeholder data
        return array(
            'total_orders' => 0,
            'total_revenue' => 0,
            'average_order_value' => 0,
        );
    }
}
