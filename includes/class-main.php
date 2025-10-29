<?php
/**
 * Main plugin class for WooDynamic Bundles
 *
 * Orchestrates all plugin components
 *
 * @package WooDynamic\Bundles
 */

namespace WooDynamic\Bundles;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main class
 */
class Main {

    /**
     * The loader that's responsible for maintaining and registering all hooks
     *
     * @var Loader
     */
    protected $loader;

    /**
     * The unique identifier of this plugin
     *
     * @var string
     */
    protected $plugin_name;

    /**
     * The current version of the plugin
     *
     * @var string
     */
    protected $version;

    /**
     * Initialize the class and set its properties
     */
    public function __construct() {
        $this->plugin_name = 'woodynamic-bundles';
        $this->version = WOODYNAMIC_BUNDLES_VERSION;

        $this->woodynamic_load_dependencies();
        $this->woodynamic_define_admin_hooks();
        $this->woodynamic_define_public_hooks();
        $this->woodynamic_define_api_hooks();
    }

    /**
     * Load the required dependencies for this plugin
     */
    private function woodynamic_load_dependencies() {
        // Core classes
        require_once WOODYNAMIC_BUNDLES_PLUGIN_DIR . 'includes/core/class-cpt-bundle-template.php';
        require_once WOODYNAMIC_BUNDLES_PLUGIN_DIR . 'includes/core/class-bundle-calculator.php';
        require_once WOODYNAMIC_BUNDLES_PLUGIN_DIR . 'includes/core/class-discount-engine.php';
        require_once WOODYNAMIC_BUNDLES_PLUGIN_DIR . 'includes/core/class-bundle-session.php';

        // Admin classes
        require_once WOODYNAMIC_BUNDLES_PLUGIN_DIR . 'admin/class-admin-menu.php';
        require_once WOODYNAMIC_BUNDLES_PLUGIN_DIR . 'admin/class-meta-boxes.php';
        require_once WOODYNAMIC_BUNDLES_PLUGIN_DIR . 'admin/class-bundle-template-editor.php';

        // Public classes
        require_once WOODYNAMIC_BUNDLES_PLUGIN_DIR . 'public/class-frontend.php';
        require_once WOODYNAMIC_BUNDLES_PLUGIN_DIR . 'public/class-shortcodes.php';
        require_once WOODYNAMIC_BUNDLES_PLUGIN_DIR . 'public/class-cart-integration.php';

        // API classes
        require_once WOODYNAMIC_BUNDLES_PLUGIN_DIR . 'api/class-rest-controller.php';
        require_once WOODYNAMIC_BUNDLES_PLUGIN_DIR . 'api/class-ajax-handler.php';
        require_once WOODYNAMIC_BUNDLES_PLUGIN_DIR . 'api/class-bundle-endpoints.php';

        $this->loader = new Loader();
    }

    /**
     * Register all of the hooks related to the admin area functionality
     */
    private function woodynamic_define_admin_hooks() {
        $admin_menu = new Admin_Menu($this->woodynamic_get_plugin_name(), $this->woodynamic_get_version());
        $meta_boxes = new Meta_Boxes($this->woodynamic_get_plugin_name(), $this->woodynamic_get_version());
        $bundle_editor = new Bundle_Template_Editor($this->woodynamic_get_plugin_name(), $this->woodynamic_get_version());

        // Admin menu
        $this->loader->woodynamic_add_action('admin_menu', $admin_menu, 'woodynamic_add_menu_pages');
        $this->loader->woodynamic_add_action('admin_enqueue_scripts', $admin_menu, 'woodynamic_enqueue_scripts');

        // Meta boxes
        $this->loader->woodynamic_add_action('add_meta_boxes', $meta_boxes, 'woodynamic_add_meta_boxes');
        $this->loader->woodynamic_add_action('save_post', $meta_boxes, 'woodynamic_save_meta_boxes', 10, 2);

        // Bundle editor
        $this->loader->woodynamic_add_action('admin_enqueue_scripts', $bundle_editor, 'woodynamic_enqueue_scripts');
        $this->loader->woodynamic_add_action('wp_ajax_woodynamic_save_bundle_template', $bundle_editor, 'woodynamic_ajax_save_bundle_template');
        $this->loader->woodynamic_add_action('wp_ajax_woodynamic_search_products', $bundle_editor, 'woodynamic_ajax_search_products');
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     */
    private function woodynamic_define_public_hooks() {
        $frontend = new Frontend($this->woodynamic_get_plugin_name(), $this->woodynamic_get_version());
        $shortcodes = new Shortcodes($this->woodynamic_get_plugin_name(), $this->woodynamic_get_version());
        $cart_integration = new Cart_Integration($this->woodynamic_get_plugin_name(), $this->woodynamic_get_version());

        // Frontend
        $this->loader->woodynamic_add_action('wp_enqueue_scripts', $frontend, 'woodynamic_enqueue_scripts');
        $this->loader->woodynamic_add_action('wp_enqueue_scripts', $frontend, 'woodynamic_enqueue_styles');

        // Shortcodes
        $this->loader->woodynamic_add_shortcode('woodynamic_bundle', $shortcodes, 'woodynamic_bundle_shortcode');

        // Cart integration
        $this->loader->woodynamic_add_action('woocommerce_cart_item_name', $cart_integration, 'woodynamic_display_bundle_info', 10, 3);
        $this->loader->woodynamic_add_action('woocommerce_cart_item_price', $cart_integration, 'woodynamic_display_bundle_price', 10, 3);
        $this->loader->woodynamic_add_action('woocommerce_cart_item_remove_link', $cart_integration, 'woodynamic_modify_bundle_remove_link', 10, 2);
        $this->loader->woodynamic_add_action('woocommerce_before_calculate_totals', $cart_integration, 'woodynamic_apply_bundle_discounts');
        $this->loader->woodynamic_add_action('woocommerce_checkout_create_order_line_item', $cart_integration, 'woodynamic_add_bundle_meta_to_order', 10, 4);
    }

    /**
     * Register all of the hooks related to the API functionality
     */
    private function woodynamic_define_api_hooks() {
        $rest_controller = new REST_Controller($this->woodynamic_get_plugin_name(), $this->woodynamic_get_version());
        $ajax_handler = new Ajax_Handler($this->woodynamic_get_plugin_name(), $this->woodynamic_get_version());

        // REST API
        $this->loader->woodynamic_add_action('rest_api_init', $rest_controller, 'woodynamic_register_routes');

        // AJAX handlers
        $this->loader->woodynamic_add_action('wp_ajax_woodynamic_calculate_bundle', $ajax_handler, 'woodynamic_calculate_bundle');
        $this->loader->woodynamic_add_action('wp_ajax_nopriv_woodynamic_calculate_bundle', $ajax_handler, 'woodynamic_calculate_bundle');
        $this->loader->woodynamic_add_action('wp_ajax_woodynamic_add_bundle_to_cart', $ajax_handler, 'woodynamic_add_bundle_to_cart');
        $this->loader->woodynamic_add_action('wp_ajax_nopriv_woodynamic_add_bundle_to_cart', $ajax_handler, 'woodynamic_add_bundle_to_cart');
        $this->loader->woodynamic_add_action('wp_ajax_woodynamic_save_bundle_config', $ajax_handler, 'woodynamic_save_bundle_config');
        $this->loader->woodynamic_add_action('wp_ajax_woodynamic_load_saved_bundles', $ajax_handler, 'woodynamic_load_saved_bundles');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress
     */
    public function woodynamic_run() {
        $this->loader->woodynamic_run();

        // Initialize core components
        $this->woodynamic_init_core_components();
    }

    /**
     * Initialize core components
     */
    private function woodynamic_init_core_components() {
        // Register CPT
        $cpt = new CPT_Bundle_Template();
        $cpt->woodynamic_init();

        // Initialize discount engine
        Discount_Engine::woodynamic_init();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of WordPress
     *
     * @return string The name of the plugin
     */
    public function woodynamic_get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin
     *
     * @return Loader Orchestrates the hooks of the plugin
     */
    public function woodynamic_get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin
     *
     * @return string The version number of the plugin
     */
    public function woodynamic_get_version() {
        return $this->version;
    }
}
