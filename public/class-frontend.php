<?php
/**
 * Frontend Class
 *
 * Handles public-facing functionality
 *
 * @package WooDynamic\Bundles
 */

namespace WooDynamic\Bundles;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Frontend class
 */
class Frontend {

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
     * Enqueue frontend scripts
     */
    public function woodynamic_enqueue_scripts() {
        // Only enqueue on pages that might have bundles
        if (!$this->woodynamic_should_enqueue_scripts()) {
            return;
        }

        wp_enqueue_script(
            $this->plugin_name . '-frontend',
            WOODYNAMIC_BUNDLES_PLUGIN_URL . 'assets/js/frontend-bundle-selector.js',
            array('jquery'),
            $this->version,
            true
        );

        wp_enqueue_script(
            $this->plugin_name . '-cart-integration',
            WOODYNAMIC_BUNDLES_PLUGIN_URL . 'assets/js/cart-integration.js',
            array('jquery'),
            $this->version,
            true
        );

        // Localize script
        wp_localize_script(
            $this->plugin_name . '-frontend',
            'woodynamic_frontend',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('woodynamic_frontend_nonce'),
                'cart_url' => wc_get_cart_url(),
                'checkout_url' => wc_get_checkout_url(),
                'currency_symbol' => get_woocommerce_currency_symbol(),
                'strings' => array(
                    'add_to_cart' => __('Add to Cart', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
                    'adding_to_cart' => __('Adding to cart...', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
                    'added_to_cart' => __('Added to cart!', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
                    'view_cart' => __('View Cart', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
                    'select_products' => __('Please select products', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
                    'minimum_items' => __('Please select at least %d items', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
                    'maximum_items' => __('Please select no more than %d items', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
                    'out_of_stock' => __('Out of stock', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
                    'save_bundle' => __('Save Bundle', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
                    'saving_bundle' => __('Saving...', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
                    'bundle_saved' => __('Bundle saved!', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
                    'error' => __('Error', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
                ),
            )
        );
    }

    /**
     * Enqueue frontend styles
     */
    public function woodynamic_enqueue_styles() {
        if (!$this->woodynamic_should_enqueue_scripts()) {
            return;
        }

        wp_enqueue_style(
            $this->plugin_name . '-frontend',
            WOODYNAMIC_BUNDLES_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Check if scripts should be enqueued
     *
     * @return bool
     */
    private function woodynamic_should_enqueue_scripts() {
        global $post;

        // Always enqueue on cart and checkout pages
        if (is_cart() || is_checkout()) {
            return true;
        }

        // Check if current post has bundle shortcode
        if ($post && has_shortcode($post->post_content, 'woodynamic_bundle')) {
            return true;
        }

        // Check if we're on a page that might have bundles
        if (is_singular() && get_post_meta($post->ID ?? 0, '_has_bundle_shortcode', true)) {
            return true;
        }

        return false;
    }

    /**
     * Add bundle-related body classes
     *
     * @param array $classes
     * @return array
     */
    public function woodynamic_body_classes($classes) {
        if ($this->woodynamic_should_enqueue_scripts()) {
            $classes[] = 'woodynamic-bundles-active';
        }

        return $classes;
    }

    /**
     * Add bundle data to WooCommerce localization
     *
     * @param array $params
     * @return array
     */
    public function woodynamic_wc_params($params) {
        $params['woodynamic_cart_fragments'] = array(
            'enabled' => 'yes',
        );

        return $params;
    }

    /**
     * Handle bundle-related AJAX requests
     */
    public function woodynamic_ajax_calculate_bundle() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'woodynamic_frontend_nonce')) {
            wp_send_json_error(__('Security check failed', WOODYNAMIC_BUNDLES_TEXT_DOMAIN));
        }

        $bundle_id = intval($_POST['bundle_id'] ?? 0);
        $products = $_POST['products'] ?? array();

        if (!$bundle_id) {
            wp_send_json_error(__('Invalid bundle ID', WOODYNAMIC_BUNDLES_TEXT_DOMAIN));
        }

        // Get bundle configuration
        $bundle_config = CPT_Bundle_Template::woodynamic_get_bundle_data($bundle_id);

        if (!$bundle_config) {
            wp_send_json_error(__('Bundle not found', WOODYNAMIC_BUNDLES_TEXT_DOMAIN));
        }

        // Validate and calculate
        $validation = Bundle_Calculator::woodynamic_validate_bundle($bundle_config, $products);

        if (!$validation['valid']) {
            wp_send_json_error(array(
                'message' => __('Bundle validation failed', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
                'errors' => $validation['errors'],
            ));
        }

        $calculation = Bundle_Calculator::woodynamic_calculate_bundle_price($bundle_config, $products);

        wp_send_json_success(array(
            'calculation' => $calculation,
            'validation' => $validation,
        ));
    }

    /**
     * Handle add bundle to cart AJAX
     */
    public function woodynamic_ajax_add_bundle_to_cart() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'woodynamic_frontend_nonce')) {
            wp_send_json_error(__('Security check failed', WOODYNAMIC_BUNDLES_TEXT_DOMAIN));
        }

        $bundle_id = intval($_POST['bundle_id'] ?? 0);
        $products = $_POST['products'] ?? array();

        if (!$bundle_id || empty($products)) {
            wp_send_json_error(__('Invalid bundle data', WOODYNAMIC_BUNDLES_TEXT_DOMAIN));
        }

        // Get bundle configuration
        $bundle_config = CPT_Bundle_Template::woodynamic_get_bundle_data($bundle_id);

        if (!$bundle_config) {
            wp_send_json_error(__('Bundle not found', WOODYNAMIC_BUNDLES_TEXT_DOMAIN));
        }

        // Validate bundle
        $validation = Bundle_Calculator::woodynamic_validate_bundle($bundle_config, $products);

        if (!$validation['valid']) {
            wp_send_json_error(array(
                'message' => __('Bundle validation failed', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
                'errors' => $validation['errors'],
            ));
        }

        // Add bundle to cart
        $cart_integration = new Cart_Integration($this->plugin_name, $this->version);
        $result = $cart_integration->woodynamic_add_bundle_to_cart($bundle_id, $products);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(array(
            'message' => __('Bundle added to cart successfully', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            'cart_url' => wc_get_cart_url(),
            'cart_count' => WC()->cart->get_cart_contents_count(),
        ));
    }

    /**
     * Handle save bundle AJAX
     */
    public function woodynamic_ajax_save_bundle() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'woodynamic_frontend_nonce')) {
            wp_send_json_error(__('Security check failed', WOODYNAMIC_BUNDLES_TEXT_DOMAIN));
        }

        $bundle_id = intval($_POST['bundle_id'] ?? 0);
        $products = $_POST['products'] ?? array();
        $bundle_name = sanitize_text_field($_POST['bundle_name'] ?? '');

        if (!$bundle_id || empty($products)) {
            wp_send_json_error(__('Invalid bundle data', WOODYNAMIC_BUNDLES_TEXT_DOMAIN));
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
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(array(
            'message' => __('Bundle saved successfully', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            'bundle_id' => $result,
        ));
    }
}
