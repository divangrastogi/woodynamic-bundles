<?php
/**
 * Bundle Builder Template
 *
 * @package WooDynamic\Bundles
 */

if (!defined('ABSPATH')) {
    exit;
}

// Variables from Shortcodes class
// $bundle_data, $products, $atts

$layout = $atts['layout'] ?? 'grid';
$columns = intval($atts['columns'] ?? 3);
$show_title = ($atts['show_title'] ?? 'yes') === 'yes';
$show_description = ($atts['show_description'] ?? 'yes') === 'yes';

$rules_text = \WooDynamic\Bundles\Shortcodes::woodynamic_get_bundle_rules_text($bundle_data['rules']);
$discount_text = \WooDynamic\Bundles\Shortcodes::woodynamic_get_bundle_discount_text($bundle_data['pricing']);
?>

<div class="woodynamic-bundle-wrapper"
      data-bundle-id="<?php echo esc_attr($bundle_data['id']); ?>"
      data-bundle-type="<?php echo esc_attr($bundle_data['type']); ?>"
      data-min-items="<?php echo esc_attr($bundle_data['rules']['min_items'] ?? 1); ?>"
      data-max-items="<?php echo esc_attr($bundle_data['rules']['max_items'] ?? 10); ?>"
      role="main"
      aria-label="<?php esc_attr_e('Bundle Builder', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?>">

    <?php if ($show_title): ?>
        <div class="bundle-header">
            <h2 class="bundle-title"><?php echo esc_html($bundle_data['title']); ?></h2>

            <?php if ($show_description && !empty($bundle_data['description'])): ?>
                <div class="bundle-description">
                    <?php echo wp_kses_post($bundle_data['description']); ?>
                </div>
            <?php endif; ?>

            <div class="bundle-rules">
                <span class="rule-item"><?php echo esc_html($rules_text); ?></span>
                <?php if ($discount_text): ?>
                    <span class="rule-discount"><?php echo esc_html($discount_text); ?></span>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="bundle-product-grid" data-layout="<?php echo esc_attr($layout); ?>" data-columns="<?php echo esc_attr($columns); ?>">
        <?php foreach ($products as $product_id => $data): ?>
            <?php
            $product = $data['product'];
            $quantity = $data['quantity'];
            $is_variable = $product->is_type('variable');
            $max_qty = $bundle_data['rules']['max_items'] ?? 10;
            ?>

            <div class="bundle-product-item"
                 data-product-id="<?php echo esc_attr($product_id); ?>"
                 data-product-type="<?php echo esc_attr($product->get_type()); ?>">

                <div class="product-image">
                    <?php echo $product->get_image('medium'); ?>
                </div>

                <h3 class="product-name">
                    <a href="<?php echo esc_url($product->get_permalink()); ?>">
                        <?php echo esc_html($product->get_name()); ?>
                    </a>
                </h3>

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
        <?php endforeach; ?>
    </div>

    <div class="bundle-summary">
        <h3><?php _e('Your Bundle', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></h3>

        <div class="bundle-progress" style="display: none;">
            <div class="progress-bar">
                <div class="progress-fill" style="width: 0%;"></div>
            </div>
            <div class="progress-text">
                <?php printf(__('Add %d more items to complete your bundle', WOODYNAMIC_BUNDLES_TEXT_DOMAIN), $bundle_data['rules']['min_items'] ?? 1); ?>
            </div>
        </div>

        <div class="selected-items">
            <!-- Selected items will be populated by JavaScript -->
        </div>

        <div class="bundle-pricing">
            <div class="pricing-row subtotal">
                <span class="label"><?php _e('Subtotal:', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></span>
                <span class="amount" data-subtotal="0"><?php echo wc_price(0); ?></span>
            </div>

            <div class="pricing-row discount" style="display: none;">
                <span class="label"><?php _e('Discount:', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></span>
                <span class="amount discount-amount" data-discount="0">-<?php echo wc_price(0); ?></span>
            </div>

            <?php if (($bundle_data['display_settings']['show_savings'] ?? 'yes') === 'yes'): ?>
                <div class="pricing-row savings" style="display: none;">
                    <span class="label"><?php _e('You Save:', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></span>
                    <span class="amount savings-amount" data-savings="0"><?php echo wc_price(0); ?></span>
                </div>
            <?php endif; ?>

            <div class="pricing-row total">
                <span class="label"><?php _e('Total:', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></span>
                <span class="amount total-amount" data-total="0"><?php echo wc_price(0); ?></span>
            </div>
        </div>

        <div class="bundle-actions">
            <button class="add-bundle-to-cart" disabled type="button">
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
</div>
