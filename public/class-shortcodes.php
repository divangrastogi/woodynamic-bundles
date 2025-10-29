<?php
/**
 * Shortcodes Class
 *
 * Handles shortcode registration and rendering
 *
 * @package WooDynamic\Bundles
 */

namespace WooDynamic\Bundles;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcodes class
 */
class Shortcodes {

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
     * Register shortcodes
     */
    public function woodynamic_register_shortcodes() {
        add_shortcode('woodynamic_bundle', array($this, 'woodynamic_bundle_shortcode'));
    }

    /**
     * Handle bundle shortcode
     *
     * @param array $atts Shortcode attributes
     * @param string $content Shortcode content
     * @return string
     */
    public function woodynamic_bundle_shortcode($atts, $content = '') {
        // Set default attributes
        $atts = shortcode_atts(array(
            'id' => 0,
            'layout' => 'grid',
            'columns' => 3,
            'show_title' => 'yes',
            'show_description' => 'yes',
        ), $atts, 'woodynamic_bundle');

        $bundle_id = intval($atts['id']);

        if (!$bundle_id) {
            return '<p>' . __('Bundle ID is required', WOODYNAMIC_BUNDLES_TEXT_DOMAIN) . '</p>';
        }

        // Get bundle data
        $bundle_data = CPT_Bundle_Template::woodynamic_get_bundle_data($bundle_id);

        if (!$bundle_data || $bundle_data['status'] !== 'active') {
            return '<p>' . __('Bundle not found or inactive', WOODYNAMIC_BUNDLES_TEXT_DOMAIN) . '</p>';
        }

        // Check if user can view this bundle
        if (!$this->woodynamic_can_user_view_bundle($bundle_data)) {
            return '<p>' . __('You do not have permission to view this bundle', WOODYNAMIC_BUNDLES_TEXT_DOMAIN) . '</p>';
        }

        // Mark post as having bundle shortcode for script enqueuing
        global $post;
        if ($post) {
            update_post_meta($post->ID, '_has_bundle_shortcode', 'yes');
        }

        // Generate bundle HTML
        ob_start();
        $this->woodynamic_render_bundle($bundle_data, $atts);
        return ob_get_clean();
    }

    /**
     * Render bundle HTML
     *
     * @param array $bundle_data
     * @param array $atts
     */
    private function woodynamic_render_bundle($bundle_data, $atts) {
        $layout = sanitize_text_field($atts['layout']);
        $columns = intval($atts['columns']);
        $show_title = $atts['show_title'] === 'yes';
        $show_description = $atts['show_description'] === 'yes';

        // Get products for the bundle
        $products = $this->woodynamic_get_bundle_products($bundle_data);

        // Include template
        $template_path = WOODYNAMIC_BUNDLES_PLUGIN_DIR . 'public/templates/bundle-builder.php';

        if (file_exists($template_path)) {
            include $template_path;
        } else {
            $this->woodynamic_render_default_bundle($bundle_data, $products, $atts);
        }
    }

    /**
     * Get products for bundle display
     *
     * @param array $bundle_data
     * @return array
     */
    private function woodynamic_get_bundle_products($bundle_data) {
        $products = array();

        if ($bundle_data['type'] === 'fixed' && !empty($bundle_data['products'])) {
            // Fixed products
            foreach ($bundle_data['products'] as $product_id => $data) {
                $product = wc_get_product($product_id);
                if ($product && $product->is_visible()) {
                    $products[$product_id] = array(
                        'product' => $product,
                        'quantity' => 1, // Default quantity for fixed bundles
                    );
                }
            }
        } elseif ($bundle_data['type'] === 'flexible') {
            // Get products from categories
            $categories = $bundle_data['rules']['required_categories'] ?? array();

            if (!empty($categories)) {
                $args = array(
                    'post_type' => 'product',
                    'post_status' => 'publish',
                    'posts_per_page' => 50,
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'product_cat',
                            'field' => 'term_id',
                            'terms' => $categories,
                        ),
                    ),
                    'meta_query' => array(
                        array(
                            'key' => '_visibility',
                            'value' => array('catalog', 'visible'),
                            'compare' => 'IN',
                        ),
                    ),
                );

                $product_posts = get_posts($args);

                foreach ($product_posts as $post) {
                    $product = wc_get_product($post->ID);
                    if ($product && $product->is_visible()) {
                        $products[$post->ID] = array(
                            'product' => $product,
                            'quantity' => 0, // Start with 0 for flexible bundles
                        );
                    }
                }
            }
        }

        return $products;
    }

    /**
     * Check if user can view bundle
     *
     * @param array $bundle_data
     * @return bool
     */
    private function woodynamic_can_user_view_bundle($bundle_data) {
        // Add any access control logic here
        // For now, all active bundles are viewable
        return true;
    }

    /**
     * Render default bundle HTML (fallback)
     *
     * @param array $bundle_data
     * @param array $products
     * @param array $atts
     */
    private function woodynamic_render_default_bundle($bundle_data, $products, $atts) {
        $layout = $atts['layout'];
        $columns = $atts['columns'];
        $show_title = $atts['show_title'] === 'yes';
        $show_description = $atts['show_description'] === 'yes';

        ?>
        <div class="woodynamic-bundle-wrapper" data-bundle-id="<?php echo esc_attr($bundle_data['id']); ?>" data-bundle-type="<?php echo esc_attr($bundle_data['type']); ?>">
            <?php if ($show_title): ?>
                <div class="bundle-header">
                    <h2 class="bundle-title"><?php echo esc_html($bundle_data['title']); ?></h2>
                    <?php if ($show_description && !empty($bundle_data['description'])): ?>
                        <div class="bundle-description"><?php echo wp_kses_post($bundle_data['description']); ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="bundle-product-grid" data-layout="<?php echo esc_attr($layout); ?>" data-columns="<?php echo esc_attr($columns); ?>">
                <?php foreach ($products as $product_id => $data): ?>
                    <?php $product = $data['product']; ?>
                    <div class="bundle-product-item" data-product-id="<?php echo esc_attr($product_id); ?>">
                        <div class="product-image">
                            <?php echo $product->get_image('medium'); ?>
                        </div>
                        <h3 class="product-name"><?php echo esc_html($product->get_name()); ?></h3>
                        <div class="product-price"><?php echo $product->get_price_html(); ?></div>

                        <?php if ($bundle_data['type'] === 'flexible'): ?>
                            <div class="quantity-selector">
                                <button class="qty-decrease">-</button>
                                <input type="number" class="qty-input" value="0" min="0" max="10">
                                <button class="qty-increase">+</button>
                            </div>
                            <button class="add-to-bundle"><?php _e('Add to Bundle', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="bundle-summary">
                <h3><?php _e('Your Bundle', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></h3>
                <div class="selected-items"></div>
                <div class="bundle-pricing">
                    <div class="subtotal">
                        <span><?php _e('Subtotal:', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></span>
                        <span class="amount">$0.00</span>
                    </div>
                    <div class="discount">
                        <span><?php _e('Discount:', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></span>
                        <span class="amount discount-amount">-$0.00</span>
                    </div>
                    <div class="total">
                        <span><?php _e('Total:', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></span>
                        <span class="amount total-amount">$0.00</span>
                    </div>
                </div>
                <div class="bundle-actions">
                    <button class="add-bundle-to-cart" disabled><?php _e('Add Bundle to Cart', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></button>
                    <button class="save-bundle"><?php _e('Save for Later', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></button>
                </div>
                <div class="bundle-errors" style="display:none;"></div>
            </div>
        </div>
        <?php
    }

    /**
     * Get bundle rules text
     *
     * @param array $rules
     * @return string
     */
    public static function woodynamic_get_bundle_rules_text($rules) {
        $min_items = $rules['min_items'] ?? 1;
        $max_items = $rules['max_items'] ?? 10;

        if ($min_items === $max_items) {
            return sprintf(__('Select %d items', WOODYNAMIC_BUNDLES_TEXT_DOMAIN), $min_items);
        } else {
            return sprintf(__('Select %d-%d items', WOODYNAMIC_BUNDLES_TEXT_DOMAIN), $min_items, $max_items);
        }
    }

    /**
     * Get bundle discount text
     *
     * @param array $pricing
     * @return string
     */
    public static function woodynamic_get_bundle_discount_text($pricing) {
        $type = $pricing['type'] ?? 'percentage';
        $value = $pricing['discount_value'] ?? 0;

        if ($type === 'percentage' && $value > 0) {
            return sprintf(__('%d%% off', WOODYNAMIC_BUNDLES_TEXT_DOMAIN), $value);
        } elseif ($type === 'fixed' && $value > 0) {
            return sprintf(__('%s off', WOODYNAMIC_BUNDLES_TEXT_DOMAIN), wc_price($value));
        } elseif ($type === 'tiered') {
            return __('Tiered pricing', WOODYNAMIC_BUNDLES_TEXT_DOMAIN);
        }

        return '';
    }
}
