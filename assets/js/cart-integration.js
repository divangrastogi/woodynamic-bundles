/**
 * Cart Integration JavaScript
 *
 * Handles cart-related interactions for bundles
 */

(function($) {
    'use strict';

    const WooDynamicCart = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Handle bundle item removal confirmation
            $(document).on('click', '.cart_item a[data-bundle-confirm]', this.confirmBundleRemoval);

            // Handle bundle group removal
            $(document).on('click', '.remove-bundle-group', this.removeBundleGroup);

            // Update cart fragments
            $(document).on('wc_fragments_refreshed', this.onFragmentsRefreshed);
        },

        confirmBundleRemoval: function(e) {
            const $link = $(this);
            const confirmMessage = $link.data('bundle-confirm');

            if (!confirm(confirmMessage)) {
                e.preventDefault();
                return false;
            }

            // If confirmed, proceed with normal removal but handle bundle logic
            const bundleGroup = $link.data('bundle-group');
            if (bundleGroup) {
                // Remove entire bundle group
                WooDynamicCart.removeBundleGroup(bundleGroup);
                e.preventDefault();
                return false;
            }
        },

        removeBundleGroup: function(groupKey) {
            // Find all items in this bundle group
            const itemsToRemove = [];

            $('.cart_item').each(function() {
                const $item = $(this);
                const itemKey = $item.find('a.remove').data('product_id');

                if ($item.find('.bundle-cart-info').length > 0) {
                    // This is a bundle item, check if it belongs to our group
                    // This would need to be implemented based on how bundle groups are identified
                    itemsToRemove.push(itemKey);
                }
            });

            // Remove items via AJAX
            if (itemsToRemove.length > 0) {
                WooDynamicCart.removeCartItems(itemsToRemove);
            }
        },

        removeCartItems: function(itemKeys) {
            // Use WooCommerce's cart AJAX to remove items
            itemKeys.forEach(function(itemKey) {
                $.ajax({
                    url: wc_cart_fragments_params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'woocommerce_remove_from_cart',
                        cart_item_key: itemKey,
                        _wpnonce: wc_cart_fragments_params.nonce
                    },
                    success: function() {
                        // Refresh cart fragments
                        $(document.body).trigger('wc_fragment_refresh');
                    }
                });
            });
        },

        onFragmentsRefreshed: function() {
            // Re-bind events after cart refresh
            WooDynamicCart.bindEvents();
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        WooDynamicCart.init();
    });

})(jQuery);
