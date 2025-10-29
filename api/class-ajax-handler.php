<?php
/**
 * AJAX Handler Class
 *
 * Handles AJAX requests for bundles
 *
 * @package WooDynamic\Bundles
 */

namespace WooDynamic\Bundles;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ajax_Handler class
 */
class Ajax_Handler {

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

        $this->woodynamic_register_ajax_actions();
    }

    /**
     * Register AJAX actions
     */
    private function woodynamic_register_ajax_actions() {
        // Admin AJAX actions
        add_action('wp_ajax_woodynamic_save_bundle_template', array($this, 'woodynamic_ajax_save_bundle_template'));
        add_action('wp_ajax_woodynamic_search_products', array($this, 'woodynamic_ajax_search_products'));

        // Frontend AJAX actions (both logged in and guest)
        add_action('wp_ajax_woodynamic_calculate_bundle', array($this, 'woodynamic_ajax_calculate_bundle'));
        add_action('wp_ajax_nopriv_woodynamic_calculate_bundle', array($this, 'woodynamic_ajax_calculate_bundle'));

        add_action('wp_ajax_woodynamic_add_bundle_to_cart', array($this, 'woodynamic_ajax_add_bundle_to_cart'));
        add_action('wp_ajax_nopriv_woodynamic_add_bundle_to_cart', array($this, 'woodynamic_ajax_add_bundle_to_cart'));

        add_action('wp_ajax_woodynamic_save_bundle_config', array($this, 'woodynamic_ajax_save_bundle_config'));
        add_action('wp_ajax_woodynamic_load_saved_bundles', array($this, 'woodynamic_ajax_load_saved_bundles'));
    }

    /**
     * AJAX handler for calculating bundle price
     */
    public function woodynamic_ajax_calculate_bundle() {
        try {
            // Verify nonce
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'woodynamic_frontend_nonce')) {
                throw new Exception(__('Security check failed', WOODYNAMIC_BUNDLES_TEXT_DOMAIN));
            }

            $bundle_id = intval($_POST['bundle_id'] ?? 0);
            $products = $_POST['products'] ?? array();

            if (!$bundle_id) {
                throw new Exception(__('Invalid bundle ID', WOODYNAMIC_BUNDLES_TEXT_DOMAIN));
            }

            // Get bundle configuration
            $bundle_config = CPT_Bundle_Template::woodynamic_get_bundle_data($bundle_id);

            if (!$bundle_config) {
                throw new Exception(__('Bundle not found', WOODYNAMIC_BUNDLES_TEXT_DOMAIN));
            }

            // Calculate price
            $calculation = Bundle_Calculator::woodynamic_calculate_bundle_price($bundle_config, $products);

            wp_send_json_success(array(
                'calculation' => $calculation,
            ));

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->get_message(),
            ));
        }
    }

    /**
     * AJAX handler for adding bundle to cart
     */
    public function woodynamic_ajax_add_bundle_to_cart() {
        try {
            // Verify nonce
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'woodynamic_frontend_nonce')) {
                throw new Exception(__('Security check failed', WOODYNAMIC_BUNDLES_TEXT_DOMAIN));
            }

            $bundle_id = intval($_POST['bundle_id'] ?? 0);
            $products = $_POST['products'] ?? array();

            if (!$bundle_id || empty($products)) {
                throw new Exception(__('Invalid bundle data', WOODYNAMIC_BUNDLES_TEXT_DOMAIN));
            }

            $cart_integration = new Cart_Integration($this->plugin_name, $this->version);
            $result = $cart_integration->woodynamic_add_bundle_to_cart($bundle_id, $products);

            if (is_wp_error($result)) {
                throw new Exception($result->get_error_message());
            }

            wp_send_json_success(array(
                'message' => __('Bundle added to cart successfully', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
                'cart_url' => wc_get_cart_url(),
                'cart_count' => WC()->cart->get_cart_contents_count(),
                'cart_total' => WC()->cart->get_total(),
            ));

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->get_message(),
            ));
        }
    }

    /**
     * AJAX handler for saving bundle configuration
     */
    public function woodynamic_ajax_save_bundle_config() {
        try {
            // Verify nonce
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'woodynamic_frontend_nonce')) {
                throw new Exception(__('Security check failed', WOODYNAMIC_BUNDLES_TEXT_DOMAIN));
            }

            $bundle_id = intval($_POST['bundle_id'] ?? 0);
            $products = $_POST['products'] ?? array();
            $bundle_name = sanitize_text_field($_POST['bundle_name'] ?? '');

            if (!$bundle_id || empty($products)) {
                throw new Exception(__('Invalid bundle data', WOODYNAMIC_BUNDLES_TEXT_DOMAIN));
            }

            $user_id = get_current_user_id();

            if (!$user_id) {
                // Save to session for guests
                $result = Bundle_Session::woodynamic_save_bundle_to_session($products, $bundle_id, $bundle_name);
            } else {
                // Save to database for logged-in users
                $result = Bundle_Session::woodynamic_save_bundle($user_id, $bundle_id, $products, $bundle_name);
            }

            if (is_wp_error($result)) {
                throw new Exception($result->get_error_message());
            }

            wp_send_json_success(array(
                'message' => __('Bundle saved successfully', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
                'bundle_id' => $result,
            ));

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->get_message(),
            ));
        }
    }

    /**
     * AJAX handler for loading saved bundles
     */
    public function woodynamic_ajax_load_saved_bundles() {
        try {
            // Verify nonce
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'woodynamic_frontend_nonce')) {
                throw new Exception(__('Security check failed', WOODYNAMIC_BUNDLES_TEXT_DOMAIN));
            }

            $user_id = get_current_user_id();

            if (!$user_id) {
                // Get from session for guests
                $bundles = Bundle_Session::woodynamic_get_session_bundles();
            } else {
                // Get from database for logged-in users
                $bundles = Bundle_Session::woodynamic_get_saved_bundles($user_id);
            }

            wp_send_json_success(array(
                'bundles' => $bundles,
            ));

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->get_message(),
            ));
        }
    }

    /**
     * AJAX handler for saving bundle template (admin)
     */
    public function woodynamic_ajax_save_bundle_template() {
        try {
            // Verify nonce and permissions
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'woodynamic_editor_nonce')) {
                throw new Exception(__('Security check failed', WOODYNAMIC_BUNDLES_TEXT_DOMAIN));
            }

            if (!current_user_can('edit_posts')) {
                throw new Exception(__('Insufficient permissions', WOODYNAMIC_BUNDLES_TEXT_DOMAIN));
            }

            $post_id = intval($_POST['post_id'] ?? 0);
            if (!$post_id) {
                throw new Exception(__('Invalid post ID', WOODYNAMIC_BUNDLES_TEXT_DOMAIN));
            }

            // Update post
            wp_update_post(array(
                'ID' => $post_id,
                'post_title' => sanitize_text_field($_POST['title'] ?? ''),
                'post_content' => wp_kses_post($_POST['content'] ?? ''),
            ));

            // Update meta
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
            ));

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->get_message(),
            ));
        }
    }

    /**
     * AJAX handler for product search (admin)
     */
    public function woodynamic_ajax_search_products() {
        try {
            // Verify nonce
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'woodynamic_editor_nonce')) {
                throw new Exception(__('Security check failed', WOODYNAMIC_BUNDLES_TEXT_DOMAIN));
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

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->get_message(),
            ));
        }
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
