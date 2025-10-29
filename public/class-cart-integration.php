<?php
/**
 * Cart Integration Class
 *
 * Handles cart and checkout integration for bundles
 *
 * @package WooDynamic\Bundles
 */

namespace WooDynamic\Bundles;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cart_Integration class
 */
class Cart_Integration {

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
     * Add bundle to cart
     *
     * @param int $bundle_id
     * @param array $products [product_id => quantity]
     * @return bool|WP_Error
     */
    public function woodynamic_add_bundle_to_cart($bundle_id, $products) {
        // Get bundle configuration
        $bundle_config = CPT_Bundle_Template::woodynamic_get_bundle_data($bundle_id);

        if (!$bundle_config) {
            return new WP_Error('bundle_not_found', __('Bundle not found', WOODYNAMIC_BUNDLES_TEXT_DOMAIN));
        }

        // Validate bundle
        $validation = Bundle_Calculator::woodynamic_validate_bundle($bundle_config, $products);

        if (!$validation['valid']) {
            return new WP_Error('validation_failed', __('Bundle validation failed', WOODYNAMIC_BUNDLES_TEXT_DOMAIN), $validation['errors']);
        }

        // Check stock for all products
        foreach ($products as $product_id => $quantity) {
            $product = wc_get_product($product_id);
            if (!$product) {
                return new WP_Error('product_not_found', __('One or more products not found', WOODYNAMIC_BUNDLES_TEXT_DOMAIN));
            }

            if (!$product->is_in_stock()) {
                return new WP_Error('out_of_stock', sprintf(__('"%s" is out of stock', WOODYNAMIC_BUNDLES_TEXT_DOMAIN), $product->get_name()));
            }

            if ($product->managing_stock() && $product->get_stock_quantity() < $quantity) {
                return new WP_Error('insufficient_stock', sprintf(__('Only %d of "%s" available', WOODYNAMIC_BUNDLES_TEXT_DOMAIN), $product->get_stock_quantity(), $product->get_name()));
            }
        }

        // Generate unique bundle group key
        $bundle_group_key = 'bundle_' . $bundle_id . '_' . uniqid();

        // Add products to cart with bundle metadata
        foreach ($products as $product_id => $quantity) {
            $product = wc_get_product($product_id);

            // Prepare cart item data
            $cart_item_data = array(
                '_bundle_id' => $bundle_id,
                '_bundle_group_key' => $bundle_group_key,
                '_bundle_products' => $products,
                '_bundle_config' => $bundle_config,
            );

            // Add to cart
            $cart_item_key = WC()->cart->add_to_cart(
                $product_id,
                $quantity,
                0, // variation_id
                array(), // variation
                $cart_item_data
            );

            if (!$cart_item_key) {
                return new WP_Error('cart_add_failed', __('Failed to add products to cart', WOODYNAMIC_BUNDLES_TEXT_DOMAIN));
            }
        }

        // Apply bundle discounts
        $this->woodynamic_apply_bundle_discounts();

        do_action('woodynamic_bundle_added_to_cart', $bundle_id, $products, $bundle_group_key);

        return true;
    }

    /**
     * Apply bundle discounts to cart
     */
    public function woodynamic_apply_bundle_discounts() {
        $cart = WC()->cart;
        $cart_contents = $cart->get_cart();

        // Group items by bundle
        $bundles = array();

        foreach ($cart_contents as $cart_item_key => $cart_item) {
            if (isset($cart_item['_bundle_group_key'])) {
                $group_key = $cart_item['_bundle_group_key'];
                if (!isset($bundles[$group_key])) {
                    $bundles[$group_key] = array(
                        'bundle_id' => $cart_item['_bundle_id'],
                        'items' => array(),
                        'config' => $cart_item['_bundle_config'] ?? null,
                    );
                }
                $bundles[$group_key]['items'][$cart_item_key] = $cart_item;
            }
        }

        // Apply discounts to each bundle
        foreach ($bundles as $group_key => $bundle_data) {
            $this->woodynamic_apply_discount_to_bundle($bundle_data);
        }
    }

    /**
     * Apply discount to a specific bundle
     *
     * @param array $bundle_data
     */
    private function woodynamic_apply_discount_to_bundle($bundle_data) {
        $bundle_id = $bundle_data['bundle_id'];
        $items = $bundle_data['items'];
        $config = $bundle_data['config'];

        if (!$config) {
            $config = CPT_Bundle_Template::woodynamic_get_bundle_data($bundle_id);
        }

        if (!$config) {
            return;
        }

        // Collect products and quantities
        $products = array();
        foreach ($items as $cart_item) {
            $product_id = $cart_item['product_id'];
            $quantity = $cart_item['quantity'];
            $products[$product_id] = $quantity;
        }

        // Calculate discount
        $calculation = Bundle_Calculator::woodynamic_calculate_bundle_price($config, $products);
        $total_discount = $calculation['discount'];

        if ($total_discount <= 0) {
            return;
        }

        // Apply proportional discount to each item
        $total_bundle_value = 0;
        foreach ($items as $cart_item) {
            $price = $cart_item['data']->get_price();
            $quantity = $cart_item['quantity'];
            $total_bundle_value += $price * $quantity;
        }

        foreach ($items as $cart_item_key => $cart_item) {
            $item_value = $cart_item['data']->get_price() * $cart_item['quantity'];
            $proportional_discount = ($item_value / $total_bundle_value) * $total_discount;

            // Update cart item price
            $new_price = max(0, $cart_item['data']->get_price() - ($proportional_discount / $cart_item['quantity']));
            WC()->cart->cart_contents[$cart_item_key]['data']->set_price($new_price);
            WC()->cart->cart_contents[$cart_item_key]['_bundle_discount'] = $proportional_discount / $cart_item['quantity'];
        }
    }

    /**
     * Display bundle info in cart item name
     *
     * @param string $item_name
     * @param array $cart_item
     * @param string $cart_item_key
     * @return string
     */
    public function woodynamic_display_bundle_info($item_name, $cart_item, $cart_item_key) {
        if (isset($cart_item['_bundle_id'])) {
            $bundle_id = $cart_item['_bundle_id'];
            $bundle_title = get_the_title($bundle_id);

            $bundle_info = sprintf(
                '<div class="bundle-cart-info">%s: %s</div>',
                __('Part of bundle', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
                esc_html($bundle_title)
            );

            $item_name .= $bundle_info;
        }

        return $item_name;
    }

    /**
     * Modify cart item price display
     *
     * @param string $price_html
     * @param array $cart_item
     * @param string $cart_item_key
     * @return string
     */
    public function woodynamic_display_bundle_price($price_html, $cart_item, $cart_item_key) {
        if (isset($cart_item['_bundle_discount']) && $cart_item['_bundle_discount'] > 0) {
            $original_price = $cart_item['data']->get_price() + $cart_item['_bundle_discount'];
            $current_price = $cart_item['data']->get_price();

            $price_html = sprintf(
                '<del>%s</del> <ins>%s</ins>',
                wc_price($original_price),
                wc_price($current_price)
            );
        }

        return $price_html;
    }

    /**
     * Modify cart item remove link for bundle items
     *
     * @param string $remove_link
     * @param string $cart_item_key
     * @return string
     */
    public function woodynamic_modify_bundle_remove_link($remove_link, $cart_item_key) {
        $cart_item = WC()->cart->get_cart_item($cart_item_key);

        if ($cart_item && isset($cart_item['_bundle_group_key'])) {
            $group_key = $cart_item['_bundle_group_key'];

            // Count items in this bundle
            $bundle_items = $this->woodynamic_get_bundle_items_in_cart($group_key);

            if (count($bundle_items) > 1) {
                // Add confirmation for removing bundle item
                $remove_link = str_replace(
                    'data-product_id=',
                    sprintf(
                        'data-bundle-confirm="%s" data-bundle-group="%s" data-product_id=',
                        esc_attr(__('Removing this item will remove the entire bundle from your cart. Continue?', WOODYNAMIC_BUNDLES_TEXT_DOMAIN)),
                        esc_attr($group_key)
                    ),
                    $remove_link
                );
            }
        }

        return $remove_link;
    }

    /**
     * Add bundle meta to order line items
     *
     * @param WC_Order_Item_Product $item
     * @param string $cart_item_key
     * @param array $values
     * @param WC_Order $order
     */
    public function woodynamic_add_bundle_meta_to_order($item, $cart_item_key, $values, $order) {
        if (isset($values['_bundle_id'])) {
            $item->add_meta_data('_bundle_id', $values['_bundle_id'], true);
            $item->add_meta_data('_bundle_group_key', $values['_bundle_group_key'], true);

            if (isset($values['_bundle_discount'])) {
                $item->add_meta_data('_bundle_discount', $values['_bundle_discount'], true);
            }

            if (isset($values['_bundle_config'])) {
                $item->add_meta_data('_bundle_config', wp_json_encode($values['_bundle_config']), true);
            }
        }
    }

    /**
     * Handle bundle removal from cart
     *
     * @param string $group_key
     */
    public function woodynamic_remove_bundle_from_cart($group_key) {
        $cart = WC()->cart->get_cart();
        $items_to_remove = array();

        foreach ($cart as $cart_item_key => $cart_item) {
            if (isset($cart_item['_bundle_group_key']) && $cart_item['_bundle_group_key'] === $group_key) {
                $items_to_remove[] = $cart_item_key;
            }
        }

        foreach ($items_to_remove as $item_key) {
            WC()->cart->remove_cart_item($item_key);
        }

        do_action('woodynamic_bundle_removed_from_cart', $group_key);
    }

    /**
     * Get bundle items in cart by group key
     *
     * @param string $group_key
     * @return array
     */
    private function woodynamic_get_bundle_items_in_cart($group_key) {
        $cart = WC()->cart->get_cart();
        $bundle_items = array();

        foreach ($cart as $cart_item_key => $cart_item) {
            if (isset($cart_item['_bundle_group_key']) && $cart_item['_bundle_group_key'] === $group_key) {
                $bundle_items[$cart_item_key] = $cart_item;
            }
        }

        return $bundle_items;
    }

    /**
     * Get bundle summary for cart
     *
     * @return array
     */
    public function woodynamic_get_cart_bundle_summary() {
        $cart = WC()->cart->get_cart();
        $bundles = array();

        foreach ($cart as $cart_item) {
            if (isset($cart_item['_bundle_id'])) {
                $bundle_id = $cart_item['_bundle_id'];
                $group_key = $cart_item['_bundle_group_key'];

                if (!isset($bundles[$group_key])) {
                    $bundle_title = get_the_title($bundle_id);
                    $bundles[$group_key] = array(
                        'bundle_id' => $bundle_id,
                        'title' => $bundle_title,
                        'items' => array(),
                        'total_discount' => 0,
                    );
                }

                $bundles[$group_key]['items'][] = $cart_item;

                if (isset($cart_item['_bundle_discount'])) {
                    $bundles[$group_key]['total_discount'] += $cart_item['_bundle_discount'] * $cart_item['quantity'];
                }
            }
        }

        return $bundles;
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

            $result = $this->woodynamic_add_bundle_to_cart($bundle_id, $products);

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
            wp_send_json_error($e->get_message());
        }
    }
}
