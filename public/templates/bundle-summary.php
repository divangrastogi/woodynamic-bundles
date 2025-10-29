<?php
/**
 * Bundle Summary Template
 *
 * @package WooDynamic\Bundles
 */

if (!defined('ABSPATH')) {
    exit;
}

// Variables expected:
// $bundle_data, $selected_products, $calculation

$calculation = $calculation ?? array(
    'subtotal' => 0,
    'discount' => 0,
    'total' => 0,
    'savings_percentage' => 0,
);
?>

<div class="bundle-summary">
    <h3><?php _e('Your Bundle', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></h3>

    <div class="selected-items">
        <?php if (!empty($selected_products)): ?>
            <?php foreach ($selected_products as $product_id => $quantity): ?>
                <?php
                $product = wc_get_product($product_id);
                if (!$product) continue;
                ?>
                <div class="selected-item" data-product-id="<?php echo esc_attr($product_id); ?>">
                    <span class="item-name"><?php echo esc_html($product->get_name()); ?></span>
                    <span class="item-qty">×<?php echo esc_html($quantity); ?></span>
                    <span class="item-price"><?php echo $product->get_price_html(); ?></span>
                    <button class="remove-item" type="button">×</button>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-items"><?php _e('No items selected yet.', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></p>
        <?php endif; ?>
    </div>

    <div class="bundle-pricing">
        <div class="pricing-row subtotal">
            <span class="label"><?php _e('Subtotal:', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></span>
            <span class="amount"><?php echo wc_price($calculation['subtotal']); ?></span>
        </div>

        <?php if ($calculation['discount'] > 0): ?>
            <div class="pricing-row discount">
                <span class="label"><?php _e('Discount:', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></span>
                <span class="amount discount-amount">-<?php echo wc_price($calculation['discount']); ?></span>
            </div>

            <?php if (($bundle_data['display_settings']['show_savings'] ?? 'yes') === 'yes'): ?>
                <div class="pricing-row savings">
                    <span class="label"><?php _e('You Save:', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></span>
                    <span class="amount savings-amount"><?php echo wc_price($calculation['discount']); ?> (<?php echo esc_html($calculation['savings_percentage']); ?>%)</span>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="pricing-row total">
            <span class="label"><?php _e('Total:', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></span>
            <span class="amount total-amount"><?php echo wc_price($calculation['total']); ?></span>
        </div>
    </div>

    <div class="bundle-actions">
        <button class="add-bundle-to-cart" <?php echo empty($selected_products) ? 'disabled' : ''; ?> type="button">
            <?php _e('Add Bundle to Cart', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?>
        </button>

        <?php if (get_option('woodynamic_allow_guest_save', 'no') === 'yes' || is_user_logged_in()): ?>
            <button class="save-bundle" type="button">
                <?php _e('Save for Later', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?>
            </button>
        <?php endif; ?>
    </div>

    <div class="bundle-errors" style="display: none;">
        <!-- Error messages will be displayed here -->
    </div>
</div>
