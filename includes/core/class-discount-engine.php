<?php
/**
 * Discount Engine Class
 *
 * Handles applying bundle discounts to WooCommerce cart and orders
 *
 * @package WooDynamic\Bundles
 */

namespace WooDynamic\Bundles;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Discount_Engine class
 */
class Discount_Engine {

    /**
     * Initialize discount hooks
     */
    public static function woodynamic_init() {
        // Cart filters
        add_filter('woocommerce_cart_item_price', array(__CLASS__, 'woodynamic_cart_item_price'), 10, 3);
        add_filter('woocommerce_cart_item_subtotal', array(__CLASS__, 'woodynamic_cart_item_subtotal'), 10, 3);
        add_filter('woocommerce_cart_item_name', array(__CLASS__, 'woodynamic_cart_item_name'), 10, 3);
        add_filter('woocommerce_cart_item_remove_link', array(__CLASS__, 'woodynamic_cart_item_remove_link'), 10, 2);

        // Checkout filters
        add_action('woocommerce_checkout_create_order_line_item', array(__CLASS__, 'woodynamic_add_bundle_meta_to_order'), 10, 4);

        // Order display
        add_filter('woocommerce_order_item_name', array(__CLASS__, 'woodynamic_order_item_name'), 10, 2);
        add_filter('woocommerce_order_item_quantity', array(__CLASS__, 'woodynamic_order_item_quantity'), 10, 3);
    }

    /**
     * Apply bundle discount to cart items
     *
     * @param array $cart_item_data
     * @return array Modified cart item data
     */
    public static function woodynamic_apply_bundle_discount($cart_item_data) {
        if (isset($cart_item_data['_bundle_id'])) {
            $bundle_id = $cart_item_data['_bundle_id'];
            $bundle_config = CPT_Bundle_Template::woodynamic_get_bundle_data($bundle_id);

            if ($bundle_config && isset($cart_item_data['_bundle_group_key'])) {
                $group_key = $cart_item_data['_bundle_group_key'];

                // Calculate proportional discount for this item
                $discount = self::woodynamic_calculate_item_discount($cart_item_data, $bundle_config, $group_key);

                if ($discount > 0) {
                    $cart_item_data['_bundle_discount'] = $discount;
                    $cart_item_data['_bundle_original_price'] = $cart_item_data['data']->get_price();
                    $cart_item_data['data']->set_price(max(0, $cart_item_data['data']->get_price() - $discount));
                }
            }
        }

        return $cart_item_data;
    }

    /**
     * Store bundle information in cart item meta
     *
     * @param array $cart_item_data
     * @param int $bundle_template_id
     * @param array $bundle_products
     * @return array
     */
    public static function woodynamic_add_bundle_to_cart($cart_item_data, $bundle_template_id, $bundle_products) {
        $bundle_group_key = 'bundle_' . $bundle_template_id . '_' . uniqid();

        $cart_item_data['_bundle_id'] = $bundle_template_id;
        $cart_item_data['_bundle_group_key'] = $bundle_group_key;
        $cart_item_data['_bundle_products'] = $bundle_products;

        return $cart_item_data;
    }

    /**
     * Modify cart item price display
     *
     * @param string $price_html
     * @param array $cart_item
     * @param string $cart_item_key
     * @return string
     */
    public static function woodynamic_cart_item_price($price_html, $cart_item, $cart_item_key) {
        if (isset($cart_item['_bundle_id'])) {
            $original_price = isset($cart_item['_bundle_original_price']) ? $cart_item['_bundle_original_price'] : $cart_item['data']->get_price();
            $current_price = $cart_item['data']->get_price();

            if ($original_price > $current_price) {
                $price_html = '<del>' . wc_price($original_price) . '</del> <ins>' . wc_price($current_price) . '</ins>';
            }
        }

        return $price_html;
    }

    /**
     * Modify cart item subtotal display
     *
     * @param string $subtotal_html
     * @param array $cart_item
     * @param string $cart_item_key
     * @return string
     */
    public static function woodynamic_cart_item_subtotal($subtotal_html, $cart_item, $cart_item_key) {
        if (isset($cart_item['_bundle_id'])) {
            $quantity = $cart_item['quantity'];
            $original_price = isset($cart_item['_bundle_original_price']) ? $cart_item['_bundle_original_price'] : $cart_item['data']->get_price();
            $current_price = $cart_item['data']->get_price();

            $original_subtotal = $original_price * $quantity;
            $current_subtotal = $current_price * $quantity;

            if ($original_subtotal > $current_subtotal) {
                $subtotal_html = '<del>' . wc_price($original_subtotal) . '</del> <ins>' . wc_price($current_subtotal) . '</ins>';
            }
        }

        return $subtotal_html;
    }

    /**
     * Modify cart item name to show bundle info
     *
     * @param string $item_name
     * @param array $cart_item
     * @param string $cart_item_key
     * @return string
     */
    public static function woodynamic_cart_item_name($item_name, $cart_item, $cart_item_key) {
        if (isset($cart_item['_bundle_id'])) {
            $bundle_id = $cart_item['_bundle_id'];
            $bundle_title = get_the_title($bundle_id);

            $bundle_info = sprintf(
                '<span class="bundle-info">%s: %s</span>',
                __('Part of bundle', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
                esc_html($bundle_title)
            );

            $item_name .= '<br>' . $bundle_info;
        }

        return $item_name;
    }

    /**
     * Modify cart item remove link for bundle items
     *
     * @param string $remove_link
     * @param string $cart_item_key
     * @return string
     */
    public static function woodynamic_cart_item_remove_link($remove_link, $cart_item_key) {
        $cart = WC()->cart->get_cart();
        $cart_item = isset($cart[$cart_item_key]) ? $cart[$cart_item_key] : null;

        if ($cart_item && isset($cart_item['_bundle_group_key'])) {
            $group_key = $cart_item['_bundle_group_key'];

            // Check if this is the only item in the bundle
            $bundle_items = self::woodynamic_get_bundle_items_in_cart($group_key);
            if (count($bundle_items) > 1) {
                // Add confirmation for removing bundle item
                $remove_link = str_replace(
                    'data-product_id=',
                    'data-bundle-confirm="' . esc_attr__('Removing this item will remove the entire bundle. Continue?', WOODYNAMIC_BUNDLES_TEXT_DOMAIN) . '" data-product_id=',
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
    public static function woodynamic_add_bundle_meta_to_order($item, $cart_item_key, $values, $order) {
        if (isset($values['_bundle_id'])) {
            $item->add_meta_data('_bundle_id', $values['_bundle_id'], true);
            $item->add_meta_data('_bundle_group_key', $values['_bundle_group_key'], true);

            if (isset($values['_bundle_discount'])) {
                $item->add_meta_data('_bundle_discount', $values['_bundle_discount'], true);
            }

            if (isset($values['_bundle_original_price'])) {
                $item->add_meta_data('_bundle_original_price', $values['_bundle_original_price'], true);
            }
        }
    }

    /**
     * Modify order item name
     *
     * @param string $item_name
     * @param WC_Order_Item_Product $item
     * @return string
     */
    public static function woodynamic_order_item_name($item_name, $item) {
        $bundle_id = $item->get_meta('_bundle_id');
        if ($bundle_id) {
            $bundle_title = get_the_title($bundle_id);
            $bundle_info = sprintf(
                '<br><small>%s: %s</small>',
                __('Part of bundle', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
                esc_html($bundle_title)
            );
            $item_name .= $bundle_info;
        }

        return $item_name;
    }

    /**
     * Modify order item quantity display
     *
     * @param string $quantity_html
     * @param int $quantity
     * @param WC_Order_Item_Product $item
     * @return string
     */
    public static function woodynamic_order_item_quantity($quantity_html, $quantity, $item) {
        // Could add bundle-specific quantity display logic here
        return $quantity_html;
    }

    /**
     * Calculate proportional discount for individual cart item
     *
     * @param array $cart_item
     * @param array $bundle_config
     * @param string $group_key
     * @return float
     */
    private static function woodynamic_calculate_item_discount($cart_item, $bundle_config, $group_key) {
        $bundle_items = self::woodynamic_get_bundle_items_in_cart($group_key);
        $total_bundle_value = 0;
        $item_value = 0;

        // Calculate total value of all bundle items
        foreach ($bundle_items as $item_key => $item) {
            $price = $item['data']->get_price();
            $quantity = $item['quantity'];
            $total_bundle_value += $price * $quantity;

            if ($item_key === $cart_item['key']) {
                $item_value = $price * $quantity;
            }
        }

        if ($total_bundle_value == 0) {
            return 0;
        }

        // Get bundle discount
        $bundle_products = array();
        foreach ($bundle_items as $item) {
            $product_id = $item['product_id'];
            $bundle_products[$product_id] = $item['quantity'];
        }

        $calculation = Bundle_Calculator::woodynamic_calculate_bundle_price($bundle_config, $bundle_products);
        $total_discount = $calculation['discount'];

        // Apply proportional discount
        $proportional_discount = ($item_value / $total_bundle_value) * $total_discount;

        return $proportional_discount;
    }

    /**
     * Get all bundle items in cart with the same group key
     *
     * @param string $group_key
     * @return array
     */
    private static function woodynamic_get_bundle_items_in_cart($group_key) {
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
     * Remove entire bundle from cart
     *
     * @param string $group_key
     */
    public static function woodynamic_remove_bundle_from_cart($group_key) {
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
    }

    /**
     * Get bundle discount summary for cart
     *
     * @return array
     */
    public static function woodynamic_get_cart_bundle_summary() {
        $cart = WC()->cart->get_cart();
        $bundles = array();

        foreach ($cart as $cart_item) {
            if (isset($cart_item['_bundle_id'])) {
                $bundle_id = $cart_item['_bundle_id'];
                $group_key = $cart_item['_bundle_group_key'];

                if (!isset($bundles[$group_key])) {
                    $bundles[$group_key] = array(
                        'bundle_id' => $bundle_id,
                        'title' => get_the_title($bundle_id),
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
}
