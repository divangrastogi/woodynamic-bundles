<?php
/**
 * Meta Boxes Class
 *
 * Handles meta boxes for bundle template editing
 *
 * @package WooDynamic\Bundles
 */

namespace WooDynamic\Bundles;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Meta_Boxes class
 */
class Meta_Boxes {

    /**
     * Initialize meta boxes
     */
    public function woodynamic_add_meta_boxes() {
        // Basic Information Meta Box
        add_meta_box(
            'woodynamic_bundle_basic_info',
            __('Bundle Information', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            array($this, 'woodynamic_render_basic_info_meta_box'),
            'wc_bundle_template',
            'normal',
            'high'
        );

        // Bundle Configuration Meta Box
        add_meta_box(
            'woodynamic_bundle_configuration',
            __('Bundle Configuration', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            array($this, 'woodynamic_render_configuration_meta_box'),
            'wc_bundle_template',
            'normal',
            'high'
        );

        // Pricing Rules Meta Box
        add_meta_box(
            'woodynamic_bundle_pricing',
            __('Pricing Rules', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            array($this, 'woodynamic_render_pricing_meta_box'),
            'wc_bundle_template',
            'normal',
            'high'
        );

        // Product Selection Meta Box
        add_meta_box(
            'woodynamic_bundle_products',
            __('Product Selection', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            array($this, 'woodynamic_render_products_meta_box'),
            'wc_bundle_template',
            'normal',
            'high'
        );

        // Display Settings Meta Box
        add_meta_box(
            'woodynamic_bundle_display',
            __('Display Settings', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            array($this, 'woodynamic_render_display_meta_box'),
            'wc_bundle_template',
            'side',
            'default'
        );
    }

    /**
     * Save meta box data
     *
     * @param int $post_id
     * @param WP_Post $post
     */
    public function woodynamic_save_meta_boxes($post_id, $post) {
        // Verify nonce
        if (!isset($_POST['woodynamic_bundle_meta_nonce']) || !wp_verify_nonce($_POST['woodynamic_bundle_meta_nonce'], 'woodynamic_bundle_meta_save')) {
            return;
        }

        // Check if user has permission
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check post type
        if ($post->post_type !== 'wc_bundle_template') {
            return;
        }

        // Save bundle type
        if (isset($_POST['_bundle_type'])) {
            update_post_meta($post_id, '_bundle_type', sanitize_text_field($_POST['_bundle_type']));
        }

        // Save bundle rules
        $rules = array(
            'min_items' => isset($_POST['_bundle_min_items']) ? absint($_POST['_bundle_min_items']) : 1,
            'max_items' => isset($_POST['_bundle_max_items']) ? absint($_POST['_bundle_max_items']) : 10,
            'allow_duplicates' => isset($_POST['_bundle_allow_duplicates']) ? 'yes' : 'no',
            'required_categories' => isset($_POST['_bundle_required_categories']) ? array_map('absint', $_POST['_bundle_required_categories']) : array(),
            'excluded_products' => isset($_POST['_bundle_excluded_products']) ? array_map('absint', $_POST['_bundle_excluded_products']) : array(),
        );
        update_post_meta($post_id, '_bundle_rules', $rules);

        // Save pricing rules
        $pricing = array(
            'type' => isset($_POST['_bundle_pricing_type']) ? sanitize_text_field($_POST['_bundle_pricing_type']) : 'percentage',
            'discount_value' => isset($_POST['_bundle_discount_value']) ? floatval($_POST['_bundle_discount_value']) : 0,
            'tiered_rules' => isset($_POST['_bundle_tiered_rules']) ? $this->woodynamic_sanitize_tiered_rules($_POST['_bundle_tiered_rules']) : array(),
        );
        update_post_meta($post_id, '_bundle_pricing', $pricing);

        // Save products
        $products = isset($_POST['_bundle_products']) ? $this->woodynamic_sanitize_bundle_products($_POST['_bundle_products']) : array();
        update_post_meta($post_id, '_bundle_products', $products);

        // Save display settings
        $display_settings = array(
            'show_individual_prices' => isset($_POST['_bundle_show_individual_prices']) ? 'yes' : 'no',
            'show_savings' => isset($_POST['_bundle_show_savings']) ? 'yes' : 'no',
            'layout' => isset($_POST['_bundle_layout']) ? sanitize_text_field($_POST['_bundle_layout']) : 'grid',
        );
        update_post_meta($post_id, '_bundle_display_settings', $display_settings);

        // Save status
        $status = isset($_POST['_bundle_status']) ? sanitize_text_field($_POST['_bundle_status']) : 'active';
        update_post_meta($post_id, '_bundle_status', $status);

        // Clear any related caches
        $this->woodynamic_clear_bundle_cache($post_id);
    }

    /**
     * Render basic information meta box
     *
     * @param WP_Post $post
     */
    public function woodynamic_render_basic_info_meta_box($post) {
        wp_nonce_field('woodynamic_bundle_meta_save', 'woodynamic_bundle_meta_nonce');
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="bundle_status"><?php _e('Status', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></label>
                </th>
                <td>
                    <select name="_bundle_status" id="bundle_status">
                        <option value="active" <?php selected(get_post_meta($post->ID, '_bundle_status', true), 'active'); ?>><?php _e('Active', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></option>
                        <option value="inactive" <?php selected(get_post_meta($post->ID, '_bundle_status', true), 'inactive'); ?>><?php _e('Inactive', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render configuration meta box
     *
     * @param WP_Post $post
     */
    public function woodynamic_render_configuration_meta_box($post) {
        $rules = get_post_meta($post->ID, '_bundle_rules', true) ?: array();
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="bundle_type"><?php _e('Bundle Type', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></label>
                </th>
                <td>
                    <select name="_bundle_type" id="bundle_type">
                        <option value="fixed" <?php selected(get_post_meta($post->ID, '_bundle_type', true), 'fixed'); ?>><?php _e('Fixed Products', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></option>
                        <option value="flexible" <?php selected(get_post_meta($post->ID, '_bundle_type', true), 'flexible'); ?>><?php _e('Flexible Selection', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></option>
                        <option value="mixed" <?php selected(get_post_meta($post->ID, '_bundle_type', true), 'mixed'); ?>><?php _e('Mixed', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></option>
                    </select>
                    <p class="description">
                        <?php _e('Fixed: Pre-selected products. Flexible: Customer chooses from categories. Mixed: Combination of both.', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label><?php _e('Item Limits', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></label>
                </th>
                <td>
                    <label><?php _e('Minimum Items:', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?>
                        <input type="number" name="_bundle_min_items" value="<?php echo esc_attr($rules['min_items'] ?? 1); ?>" min="1" max="50">
                    </label>
                    <br>
                    <label><?php _e('Maximum Items:', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?>
                        <input type="number" name="_bundle_max_items" value="<?php echo esc_attr($rules['max_items'] ?? 10); ?>" min="1" max="100">
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="allow_duplicates"><?php _e('Allow Duplicates', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></label>
                </th>
                <td>
                    <input type="checkbox" name="_bundle_allow_duplicates" id="allow_duplicates" value="yes" <?php checked(($rules['allow_duplicates'] ?? 'no'), 'yes'); ?>>
                    <span class="description"><?php _e('Allow the same product to be added multiple times.', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></span>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label><?php _e('Category Restrictions', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></label>
                </th>
                <td>
                    <select name="_bundle_required_categories[]" multiple="multiple" style="width: 100%; max-width: 400px;">
                        <?php
                        $selected_categories = $rules['required_categories'] ?? array();
                        $categories = get_terms(array(
                            'taxonomy' => 'product_cat',
                            'hide_empty' => false,
                        ));

                        foreach ($categories as $category) {
                            $selected = in_array($category->term_id, $selected_categories) ? 'selected' : '';
                            echo '<option value="' . esc_attr($category->term_id) . '" ' . $selected . '>' . esc_html($category->name) . '</option>';
                        }
                        ?>
                    </select>
                    <p class="description"><?php _e('Limit products to these categories (leave empty for no restrictions).', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render pricing meta box
     *
     * @param WP_Post $post
     */
    public function woodynamic_render_pricing_meta_box($post) {
        $pricing = get_post_meta($post->ID, '_bundle_pricing', true) ?: array();
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="pricing_type"><?php _e('Discount Type', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></label>
                </th>
                <td>
                    <select name="_bundle_pricing_type" id="pricing_type">
                        <option value="percentage" <?php selected(($pricing['type'] ?? 'percentage'), 'percentage'); ?>><?php _e('Percentage Discount', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></option>
                        <option value="fixed" <?php selected(($pricing['type'] ?? 'percentage'), 'fixed'); ?>><?php _e('Fixed Amount Discount', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></option>
                        <option value="tiered" <?php selected(($pricing['type'] ?? 'percentage'), 'tiered'); ?>><?php _e('Tiered Pricing', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></option>
                    </select>
                </td>
            </tr>

            <tr class="discount-value-row" style="<?php echo (($pricing['type'] ?? 'percentage') === 'tiered') ? 'display: none;' : ''; ?>">
                <th scope="row">
                    <label for="discount_value"><?php _e('Discount Value', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></label>
                </th>
                <td>
                    <input type="number" name="_bundle_discount_value" id="discount_value" step="0.01" min="0" max="100"
                           value="<?php echo esc_attr($pricing['discount_value'] ?? 0); ?>">
                    <span class="discount-unit"><?php echo (($pricing['type'] ?? 'percentage') === 'fixed') ? get_woocommerce_currency_symbol() : '%'; ?></span>
                </td>
            </tr>

            <tr class="tiered-rules-row" style="<?php echo (($pricing['type'] ?? 'percentage') === 'tiered') ? '' : 'display: none;'; ?>">
                <th scope="row">
                    <label><?php _e('Tiered Rules', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></label>
                </th>
                <td>
                    <div id="tiered-rules-container">
                        <?php
                        $tiered_rules = $pricing['tiered_rules'] ?? array();
                        if (!empty($tiered_rules)) {
                            foreach ($tiered_rules as $index => $rule) {
                                ?>
                                <div class="tiered-rule">
                                    <label><?php _e('Items:', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?>
                                        <input type="number" name="_bundle_tiered_rules[<?php echo $index; ?>][qty]" value="<?php echo esc_attr($rule['qty']); ?>" min="1">
                                    </label>
                                    <label><?php _e('Discount:', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?>
                                        <input type="number" name="_bundle_tiered_rules[<?php echo $index; ?>][discount]" value="<?php echo esc_attr($rule['discount']); ?>" step="0.01" min="0" max="100">%
                                    </label>
                                    <button type="button" class="remove-tier button"><?php _e('Remove', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></button>
                                </div>
                                <?php
                            }
                        }
                        ?>
                    </div>
                    <button type="button" id="add-tier" class="button"><?php _e('Add Tier', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></button>
                    <p class="description"><?php _e('Define discount tiers based on quantity. Higher quantities get better discounts.', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render products meta box
     *
     * @param WP_Post $post
     */
    public function woodynamic_render_products_meta_box($post) {
        $products = get_post_meta($post->ID, '_bundle_products', true) ?: array();
        $bundle_type = get_post_meta($post->ID, '_bundle_type', true);
        ?>
        <div id="bundle-products-container">
            <?php if ($bundle_type === 'fixed'): ?>
                <div id="fixed-products-list">
                    <?php
                    if (!empty($products)) {
                        foreach ($products as $product_id => $data) {
                            $product = wc_get_product($product_id);
                            if (!$product) continue;
                            ?>
                            <div class="bundle-product-item" data-product-id="<?php echo esc_attr($product_id); ?>">
                                <img src="<?php echo esc_url(get_the_post_thumbnail_url($product_id, 'thumbnail')); ?>" alt="" style="width: 50px; height: 50px;">
                                <span class="product-name"><?php echo esc_html($product->get_name()); ?></span>
                                <span class="product-price"><?php echo $product->get_price_html(); ?></span>
                                <input type="hidden" name="_bundle_products[<?php echo esc_attr($product_id); ?>][id]" value="<?php echo esc_attr($product_id); ?>">
                                <button type="button" class="remove-product button">×</button>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </div>
                <div class="product-search-container">
                    <input type="text" id="product-search" placeholder="<?php esc_attr_e('Search products...', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?>" style="width: 100%;">
                    <div id="product-search-results"></div>
                </div>
            <?php else: ?>
                <p><?php _e('For flexible bundles, products are selected by customers based on the category restrictions above.', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render display settings meta box
     *
     * @param WP_Post $post
     */
    public function woodynamic_render_display_meta_box($post) {
        $display_settings = get_post_meta($post->ID, '_bundle_display_settings', true) ?: array();
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="show_individual_prices"><?php _e('Show Individual Prices', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></label>
                </th>
                <td>
                    <input type="checkbox" name="_bundle_show_individual_prices" id="show_individual_prices" value="yes" <?php checked(($display_settings['show_individual_prices'] ?? 'yes'), 'yes'); ?>>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="show_savings"><?php _e('Show Savings', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></label>
                </th>
                <td>
                    <input type="checkbox" name="_bundle_show_savings" id="show_savings" value="yes" <?php checked(($display_settings['show_savings'] ?? 'yes'), 'yes'); ?>>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="bundle_layout"><?php _e('Layout', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></label>
                </th>
                <td>
                    <select name="_bundle_layout" id="bundle_layout">
                        <option value="grid" <?php selected(($display_settings['layout'] ?? 'grid'), 'grid'); ?>><?php _e('Grid', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></option>
                        <option value="list" <?php selected(($display_settings['layout'] ?? 'grid'), 'list'); ?>><?php _e('List', WOODYNAMIC_BUNDLES_TEXT_DOMAIN); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Sanitize tiered rules
     *
     * @param array $rules
     * @return array
     */
    private function woodynamic_sanitize_tiered_rules($rules) {
        $sanitized = array();

        if (is_array($rules)) {
            foreach ($rules as $rule) {
                if (isset($rule['qty']) && isset($rule['discount'])) {
                    $sanitized[] = array(
                        'qty' => absint($rule['qty']),
                        'discount' => floatval($rule['discount']),
                    );
                }
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize bundle products
     *
     * @param array $products
     * @return array
     */
    private function woodynamic_sanitize_bundle_products($products) {
        $sanitized = array();

        if (is_array($products)) {
            foreach ($products as $product_id => $data) {
                $product_id = absint($product_id);
                if ($product_id > 0) {
                    $sanitized[$product_id] = array(
                        'id' => $product_id,
                    );
                }
            }
        }

        return $sanitized;
    }

    /**
     * Clear bundle-related caches
     *
     * @param int $post_id
     */
    private function woodynamic_clear_bundle_cache($post_id) {
        // Clear any transients related to this bundle
        $cache_key = 'woodynamic_bundle_' . $post_id;
        delete_transient($cache_key);

        // Clear general bundle cache
        wp_cache_delete('woodynamic_bundles', 'options');
    }
}
