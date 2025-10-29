<?php
/**
 * Bundle Template Editor Class
 *
 * Handles JavaScript integration for bundle template editing
 *
 * @package WooDynamic\Bundles
 */

namespace WooDynamic\Bundles;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bundle_Template_Editor class
 */
class Bundle_Template_Editor {

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
     * Enqueue scripts and styles for the editor
     */
    public function woodynamic_enqueue_scripts() {
        $screen = get_current_screen();

        if ($screen->post_type !== 'wc_bundle_template') {
            return;
        }

        // Enqueue Select2 for category selection
        wp_enqueue_script('select2');
        wp_enqueue_style('select2');

        // Enqueue our custom scripts
        wp_enqueue_script(
            $this->plugin_name . '-editor',
            WOODYNAMIC_BUNDLES_PLUGIN_URL . 'assets/js/admin-bundle-builder.js',
            array('jquery', 'select2', 'jquery-ui-sortable'),
            $this->version,
            true
        );

        // Localize script with data
        wp_localize_script(
            $this->plugin_name . '-editor',
            'woodynamic_editor',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('woodynamic_editor_nonce'),
                'post_id' => get_the_ID(),
                'strings' => array(
                    'search_products' => __('Search products...', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
                    'no_products_found' => __('No products found', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
                    'add_to_bundle' => __('Add to Bundle', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
                    'remove_from_bundle' => __('Remove', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
                    'confirm_remove' => __('Are you sure you want to remove this product?', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
                ),
            )
        );
    }

    /**
     * AJAX handler for saving bundle template
     */
    public function woodynamic_ajax_save_bundle_template() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'woodynamic_editor_nonce')) {
            wp_send_json_error(__('Security check failed', WOODYNAMIC_BUNDLES_TEXT_DOMAIN));
        }

        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Insufficient permissions', WOODYNAMIC_BUNDLES_TEXT_DOMAIN));
        }

        $post_id = intval($_POST['post_id'] ?? 0);
        if (!$post_id) {
            wp_send_json_error(__('Invalid post ID', WOODYNAMIC_BUNDLES_TEXT_DOMAIN));
        }

        // Save the data
        $result = wp_update_post(array(
            'ID' => $post_id,
            'post_title' => sanitize_text_field($_POST['title'] ?? ''),
            'post_content' => wp_kses_post($_POST['content'] ?? ''),
        ));

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        // Save meta data
        $meta_updates = array(
            '_bundle_type' => sanitize_text_field($_POST['bundle_type'] ?? 'flexible'),
            '_bundle_status' => sanitize_text_field($_POST['bundle_status'] ?? 'active'),
            '_bundle_rules' => $this->woodynamic_sanitize_bundle_rules($_POST['bundle_rules'] ?? array()),
            '_bundle_pricing' => $this->woodynamic_sanitize_bundle_pricing($_POST['bundle_pricing'] ?? array()),
            '_bundle_products' => $this->woodynamic_sanitize_bundle_products($_POST['bundle_products'] ?? array()),
            '_bundle_display_settings' => $this->woodynamic_sanitize_display_settings($_POST['display_settings'] ?? array()),
        );

        foreach ($meta_updates as $key => $value) {
            update_post_meta($post_id, $key, $value);
        }

        wp_send_json_success(array(
            'message' => __('Bundle template saved successfully', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            'post_id' => $post_id,
        ));
    }

    /**
     * AJAX handler for product search
     */
    public function woodynamic_ajax_search_products() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'woodynamic_editor_nonce')) {
            wp_send_json_error(__('Security check failed', WOODYNAMIC_BUNDLES_TEXT_DOMAIN));
        }

        $search_term = sanitize_text_field($_POST['search'] ?? '');
        $exclude_ids = array_map('intval', $_POST['exclude'] ?? array());

        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => 20,
            's' => $search_term,
            'post__not_in' => $exclude_ids,
            'meta_query' => array(
                array(
                    'key' => '_visibility',
                    'value' => array('catalog', 'visible'),
                    'compare' => 'IN',
                ),
            ),
        );

        $products = get_posts($args);
        $results = array();

        foreach ($products as $product) {
            $wc_product = wc_get_product($product->ID);
            if (!$wc_product) continue;

            $results[] = array(
                'id' => $product->ID,
                'text' => $product->post_title,
                'price' => $wc_product->get_price_html(),
                'thumbnail' => get_the_post_thumbnail_url($product->ID, 'thumbnail'),
                'stock_status' => $wc_product->get_stock_status(),
            );
        }

        wp_send_json_success($results);
    }

    /**
     * Sanitize bundle rules
     *
     * @param array $rules
     * @return array
     */
    private function woodynamic_sanitize_bundle_rules($rules) {
        return array(
            'min_items' => isset($rules['min_items']) ? absint($rules['min_items']) : 1,
            'max_items' => isset($rules['max_items']) ? absint($rules['max_items']) : 10,
            'allow_duplicates' => isset($rules['allow_duplicates']) ? 'yes' : 'no',
            'required_categories' => isset($rules['required_categories']) ? array_map('absint', $rules['required_categories']) : array(),
            'excluded_products' => isset($rules['excluded_products']) ? array_map('absint', $rules['excluded_products']) : array(),
        );
    }

    /**
     * Sanitize bundle pricing
     *
     * @param array $pricing
     * @return array
     */
    private function woodynamic_sanitize_bundle_pricing($pricing) {
        return array(
            'type' => isset($pricing['type']) ? sanitize_text_field($pricing['type']) : 'percentage',
            'discount_value' => isset($pricing['discount_value']) ? floatval($pricing['discount_value']) : 0,
            'tiered_rules' => isset($pricing['tiered_rules']) ? $this->woodynamic_sanitize_tiered_rules($pricing['tiered_rules']) : array(),
        );
    }

    /**
     * Sanitize bundle products
     *
     * @param array $products
     * @return array
     */
    private function woodynamic_sanitize_bundle_products($products) {
        $sanitized = array();

        if (is_array($products)) {
            foreach ($products as $product_id) {
                $product_id = absint($product_id);
                if ($product_id > 0) {
                    $sanitized[$product_id] = array('id' => $product_id);
                }
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize display settings
     *
     * @param array $settings
     * @return array
     */
    private function woodynamic_sanitize_display_settings($settings) {
        return array(
            'show_individual_prices' => isset($settings['show_individual_prices']) ? 'yes' : 'no',
            'show_savings' => isset($settings['show_savings']) ? 'yes' : 'no',
            'layout' => isset($settings['layout']) ? sanitize_text_field($settings['layout']) : 'grid',
        );
    }

    /**
     * Sanitize tiered rules
     *
     * @param array $rules
     * @return array
     */
    private function woodynamic_sanitize_tiered_rules($rules) {
        $sanitized = array();

        if (is_array($rules)) {
            foreach ($rules as $rule) {
                if (isset($rule['qty']) && isset($rule['discount'])) {
                    $sanitized[] = array(
                        'qty' => absint($rule['qty']),
                        'discount' => floatval($rule['discount']),
                    );
                }
            }
        }

        return $sanitized;
    }
}
