<?php
/**
 * Bundle Item Template
 *
 * @package WooDynamic\Bundles
 */

if (!defined('ABSPATH')) {
    exit;
}

// Variables expected:
// $product, $quantity, $bundle_data

$is_variable = $product->is_type('variable');
$max_qty = $bundle_data['rules']['max_items'] ?? 10;
?>

<div class="bundle-product-item"
     data-product-id="<?php echo esc_attr($product->get_id()); ?>"
     data-product-type="<?php echo esc_attr($product->get_type()); ?>">

    <div class="product-image">
        <?php echo $product->get_image('medium'); ?>
    </div>

    <h4 class="product-name">
        <a href="<?php echo esc_url($product->get_permalink()); ?>">
            <?php echo esc_html($product->get_name()); ?>
        </a>
    </h4>

    <?php if (($bundle_data['display_settings']['show_individual_prices'] ?? 'yes') === 'yes'): ?>
        <div class="product-price">
            <?php echo $product->get_price_html(); ?>
        </div>
    <?php endif; ?>

    <?php if ($bundle_data['type'] === 'flexible' || $bundle_data['type'] === 'mixed'): ?>
        <div class="quantity-selector">
            <button class="qty-decrease" type="button">-</button>
            <input type="number"
                   class="qty-input"
                   value="<?php echo esc_attr($quantity); ?>"
                   min="0"
                   max="<?php echo esc_attr($max_qty); ?>"
                   step="1">
            <button class="qty-increase" type="button">+</button>
        </div>

        <button class="add-to-bundle" type="button">
            <?php _e('Add to Bundle', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?>
        </button>
    <?php endif; ?>

    <?php if (!$product->is_in_stock()): ?>
        <div class="out-of-stock">
            <?php _e('Out of stock', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?>
        </div>
    <?php endif; ?>
</div>
