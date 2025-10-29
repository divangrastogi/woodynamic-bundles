<?php
/**
 * Bundle Calculator Class
 *
 * Handles all bundle price calculations and validations
 *
 * @package WooDynamic\Bundles
 */

namespace WooDynamic\Bundles;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bundle_Calculator class
 */
class Bundle_Calculator {

    /**
     * Calculate bundle total with discounts
     *
     * @param array $bundle_config Bundle configuration
     * @param array $selected_products [product_id => quantity]
     * @return array [subtotal, discount, total, savings_percentage]
     */
    public static function woodynamic_calculate_bundle_price($bundle_config, $selected_products) {
        $subtotal = self::woodynamic_calculate_subtotal($selected_products);
        $discount = self::woodynamic_calculate_discount($bundle_config, $selected_products, $subtotal);
        $total = max(0, $subtotal - $discount);
        $savings_percentage = $subtotal > 0 ? round(($discount / $subtotal) * 100, 2) : 0;

        return apply_filters('woodynamic_bundle_calculated_price', array(
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
            'savings_percentage' => $savings_percentage,
            'currency' => get_woocommerce_currency_symbol(),
        ), $bundle_config, $selected_products);
    }

    /**
     * Validate bundle selection against rules
     *
     * @param array $bundle_config
     * @param array $selected_products
     * @return array ['valid' => bool, 'errors' => []]
     */
    public static function woodynamic_validate_bundle($bundle_config, $selected_products) {
        $errors = array();
        $rules = isset($bundle_config['rules']) ? $bundle_config['rules'] : array();

        // Check minimum items
        $min_items = isset($rules['min_items']) ? intval($rules['min_items']) : 1;
        $total_quantity = array_sum($selected_products);

        if ($total_quantity < $min_items) {
            $errors[] = sprintf(
                __('Please select at least %d items.', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
                $min_items
            );
        }

        // Check maximum items
        $max_items = isset($rules['max_items']) ? intval($rules['max_items']) : 10;
        if ($total_quantity > $max_items) {
            $errors[] = sprintf(
                __('Please select no more than %d items.', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
                $max_items
            );
        }

        // Check category restrictions
        if (!empty($rules['required_categories'])) {
            $allowed_categories = $rules['required_categories'];
            foreach (array_keys($selected_products) as $product_id) {
                $product_categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
                $has_allowed_category = !empty(array_intersect($product_categories, $allowed_categories));
                if (!$has_allowed_category) {
                    $product = wc_get_product($product_id);
                    $errors[] = sprintf(
                        __('"%s" is not in an allowed category.', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
                        $product ? $product->get_name() : __('Unknown product', WOODYNAMIC_BUNDLES_TEXT_DOMAIN)
                    );
                }
            }
        }

        // Check excluded products
        if (!empty($rules['excluded_products'])) {
            $excluded_products = $rules['excluded_products'];
            foreach (array_keys($selected_products) as $product_id) {
                if (in_array($product_id, $excluded_products)) {
                    $product = wc_get_product($product_id);
                    $errors[] = sprintf(
                        __('"%s" is not allowed in this bundle.', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
                        $product ? $product->get_name() : __('Unknown product', WOODYNAMIC_BUNDLES_TEXT_DOMAIN)
                    );
                }
            }
        }

        // Check stock availability
        foreach ($selected_products as $product_id => $quantity) {
            $product = wc_get_product($product_id);
            if (!$product) {
                $errors[] = __('One or more selected products are no longer available.', WOODYNAMIC_BUNDLES_TEXT_DOMAIN);
                continue;
            }

            if (!$product->is_in_stock()) {
                $errors[] = sprintf(
                    __('"%s" is out of stock.', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
                    $product->get_name()
                );
            } elseif ($product->managing_stock() && $product->get_stock_quantity() < $quantity) {
                $errors[] = sprintf(
                    __('Only %d of "%s" available in stock.', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
                    $product->get_stock_quantity(),
                    $product->get_name()
                );
            }
        }

        return array(
            'valid' => empty($errors),
            'errors' => $errors,
        );
    }

    /**
     * Apply tiered discount based on quantity
     *
     * @param array $tiered_rules
     * @param int $total_quantity
     * @return float Discount percentage/amount
     */
    public static function woodynamic_get_tiered_discount($tiered_rules, $total_quantity) {
        if (empty($tiered_rules) || !is_array($tiered_rules)) {
            return 0;
        }

        // Sort tiers by quantity ascending
        usort($tiered_rules, function($a, $b) {
            return intval($a['qty']) - intval($b['qty']);
        });

        $discount = 0;
        foreach ($tiered_rules as $tier) {
            if ($total_quantity >= intval($tier['qty'])) {
                $discount = floatval($tier['discount']);
            } else {
                break;
            }
        }

        return $discount;
    }

    /**
     * Get individual product price (with variations support)
     *
     * @param int $product_id
     * @param int $variation_id
     * @return float
     */
    public static function woodynamic_get_product_price($product_id, $variation_id = 0) {
        $product = wc_get_product($variation_id ?: $product_id);

        if (!$product) {
            return 0;
        }

        // Get the active price (sale price if active, otherwise regular price)
        $price = $product->get_price();

        return floatval($price);
    }

    /**
     * Calculate subtotal for selected products
     *
     * @param array $selected_products [product_id => quantity]
     * @return float
     */
    private static function woodynamic_calculate_subtotal($selected_products) {
        $subtotal = 0;

        foreach ($selected_products as $product_id => $quantity) {
            $price = self::woodynamic_get_product_price($product_id);
            $subtotal += $price * intval($quantity);
        }

        return $subtotal;
    }

    /**
     * Calculate discount amount
     *
     * @param array $bundle_config
     * @param array $selected_products
     * @param float $subtotal
     * @return float
     */
    private static function woodynamic_calculate_discount($bundle_config, $selected_products, $subtotal) {
        $pricing = isset($bundle_config['pricing']) ? $bundle_config['pricing'] : array();
        $discount_type = isset($pricing['type']) ? $pricing['type'] : 'percentage';
        $discount_value = isset($pricing['discount_value']) ? floatval($pricing['discount_value']) : 0;
        $total_quantity = array_sum($selected_products);

        // Apply tiered discount if available
        if ($discount_type === 'tiered' && !empty($pricing['tiered_rules'])) {
            $tiered_discount = self::woodynamic_get_tiered_discount($pricing['tiered_rules'], $total_quantity);
            if ($tiered_discount > 0) {
                if (strpos($tiered_discount, '%') !== false) {
                    // Percentage discount
                    $percentage = floatval(str_replace('%', '', $tiered_discount));
                    return ($subtotal * $percentage) / 100;
                } else {
                    // Fixed amount discount
                    return min($tiered_discount, $subtotal);
                }
            }
        }

        // Apply standard discount
        if ($discount_value > 0) {
            if ($discount_type === 'percentage') {
                return ($subtotal * $discount_value) / 100;
            } elseif ($discount_type === 'fixed') {
                return min($discount_value, $subtotal);
            }
        }

        return 0;
    }

    /**
     * Check if bundle meets minimum requirements
     *
     * @param array $bundle_config
     * @param array $selected_products
     * @return bool
     */
    public static function woodynamic_meets_minimum_requirements($bundle_config, $selected_products) {
        $rules = isset($bundle_config['rules']) ? $bundle_config['rules'] : array();
        $min_items = isset($rules['min_items']) ? intval($rules['min_items']) : 1;
        $total_quantity = array_sum($selected_products);

        return $total_quantity >= $min_items;
    }

    /**
     * Get bundle pricing breakdown
     *
     * @param array $bundle_config
     * @param array $selected_products
     * @return array
     */
    public static function woodynamic_get_pricing_breakdown($bundle_config, $selected_products) {
        $breakdown = array();
        $subtotal = 0;

        foreach ($selected_products as $product_id => $quantity) {
            $product = wc_get_product($product_id);
            if (!$product) continue;

            $price = self::woodynamic_get_product_price($product_id);
            $item_total = $price * intval($quantity);
            $subtotal += $item_total;

            $breakdown[] = array(
                'product_id' => $product_id,
                'name' => $product->get_name(),
                'price' => $price,
                'quantity' => $quantity,
                'total' => $item_total,
            );
        }

        $calculation = self::woodynamic_calculate_bundle_price($bundle_config, $selected_products);

        return array(
            'items' => $breakdown,
            'subtotal' => $subtotal,
            'discount' => $calculation['discount'],
            'total' => $calculation['total'],
            'savings_percentage' => $calculation['savings_percentage'],
        );
    }
}
