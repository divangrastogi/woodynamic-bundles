<?php
/**
 * Plugin Name: WooDynamic Bundles
 * Plugin URI: https://wbcomdesigns.com/
 * Description: Allow customers to create dynamic product bundles with live pricing updates for WooCommerce.
 * Version: 1.0.0
 * Author: WBCom Designs
 * Author URI: https://wbcomdesigns.com/
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: woodynamic-bundles
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 * WC tested up to: 8.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WOODYNAMIC_BUNDLES_VERSION', '1.0.0');
define('WOODYNAMIC_BUNDLES_PLUGIN_FILE', __FILE__);
define('WOODYNAMIC_BUNDLES_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WOODYNAMIC_BUNDLES_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WOODYNAMIC_BUNDLES_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('WOODYNAMIC_BUNDLES_TEXT_DOMAIN', 'woodynamic-bundles');

/**
 * Check if WooCommerce is active
 */
function woodynamic_bundles_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'woodynamic_bundles_woocommerce_missing_notice');
        return false;
    }
    return true;
}

/**
 * Display notice if WooCommerce is not active
 */
function woodynamic_bundles_woocommerce_missing_notice() {
    ?>
    <div class="error">
        <p><?php esc_html_e('WooDynamic Bundles requires WooCommerce to be installed and active.', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></p>
    </div>
    <?php
}

/**
 * Main plugin initialization
 */
function woodynamic_bundles_init() {
    if (!woodynamic_bundles_check_woocommerce()) {
        return;
    }

    // Load plugin textdomain
    load_plugin_textdomain(WOODYNAMIC_BUNDLES_TEXT_DOMAIN, false, dirname(WOODYNAMIC_BUNDLES_PLUGIN_BASENAME) . '/languages');

    // Include core files
    require_once WOODYNAMIC_BUNDLES_PLUGIN_DIR . 'includes/class-activator.php';
    require_once WOODYNAMIC_BUNDLES_PLUGIN_DIR . 'includes/class-deactivator.php';
    require_once WOODYNAMIC_BUNDLES_PLUGIN_DIR . 'includes/class-loader.php';
    require_once WOODYNAMIC_BUNDLES_PLUGIN_DIR . 'includes/class-main.php';

    // Initialize the main plugin class
    $plugin = new WooDynamic\Bundles\Main();
    $plugin->woodynamic_run();
}

// Include activator and deactivator for hooks
require_once WOODYNAMIC_BUNDLES_PLUGIN_DIR . 'includes/class-activator.php';
require_once WOODYNAMIC_BUNDLES_PLUGIN_DIR . 'includes/class-deactivator.php';

// Register activation and deactivation hooks
register_activation_hook(__FILE__, array('WooDynamic\Bundles\Activator', 'woodynamic_activate'));
register_deactivation_hook(__FILE__, array('WooDynamic\Bundles\Deactivator', 'woodynamic_deactivate'));

// Initialize plugin
add_action('plugins_loaded', 'woodynamic_bundles_init');

/**
 * Get the main plugin instance
 *
 * @return WooDynamic\Bundles\Main
 */
function woodynamic_bundles() {
    static $instance = null;
    if (null === $instance) {
        $instance = new WooDynamic\Bundles\Main();
    }
    return $instance;
}