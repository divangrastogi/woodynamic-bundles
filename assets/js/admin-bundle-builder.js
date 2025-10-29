/**
 * Admin Bundle Builder JavaScript
 *
 * Handles admin interface interactions for bundle templates
 */

(function($) {
    'use strict';

    const WooDynamicAdmin = {
        init: function() {
            this.bindEvents();
            this.initPricingTypeToggle();
            this.initProductSearch();
            this.initCategorySelector();
        },

        bindEvents: function() {
            // Pricing type change
            $(document).on('change', '#pricing_type', this.handlePricingTypeChange);

            // Add tier button
            $(document).on('click', '#add-tier', this.addTierRule);

            // Remove tier button
            $(document).on('click', '.remove-tier', this.removeTierRule);

            // Product search
            $(document).on('input', '#product-search', this.handleProductSearch);
            $(document).on('click', '.product-search-result', this.addProductToBundle);

            // Remove product from bundle
            $(document).on('click', '.remove-product', this.removeProductFromBundle);

            // Save bundle template
            $(document).on('click', '#publish, #save-post', this.saveBundleTemplate);
        },

        initPricingTypeToggle: function() {
            const pricingType = $('#pricing_type').val();
            this.togglePricingFields(pricingType);
        },

        handlePricingTypeChange: function() {
            const pricingType = $(this).val();
            WooDynamicAdmin.togglePricingFields(pricingType);
        },

        togglePricingFields: function(type) {
            if (type === 'tiered') {
                $('.discount-value-row').hide();
                $('.tiered-rules-row').show();
            } else {
                $('.discount-value-row').show();
                $('.tiered-rules-row').hide();
            }
        },

        addTierRule: function() {
            const tierCount = $('.tiered-rule').length + 1;
            const tierHtml = `
                <div class="tiered-rule">
                    <label>Items: <input type="number" name="_bundle_tiered_rules[${tierCount}][qty]" value="1" min="1"></label>
                    <label>Discount: <input type="number" name="_bundle_tiered_rules[${tierCount}][discount]" value="0" step="0.01" min="0" max="100">%</label>
                    <button type="button" class="remove-tier button">Remove</button>
                </div>
            `;
            $('#tiered-rules-container').append(tierHtml);
        },

        removeTierRule: function() {
            $(this).closest('.tiered-rule').remove();
        },

        initProductSearch: function() {
            // Initialize Select2 for category selector
            $('#bundle_required_categories').select2({
                placeholder: 'Select categories...',
                allowClear: true,
                width: '100%'
            });
        },

        initCategorySelector: function() {
            // Category selector is handled by Select2
        },

        handleProductSearch: function() {
            const searchTerm = $(this).val().trim();
            const $results = $('.product-search-results');

            if (searchTerm.length < 2) {
                $results.empty().hide();
                return;
            }

            // Get excluded product IDs
            const excludedIds = [];
            $('.bundle-product-item').each(function() {
                excludedIds.push($(this).data('product-id'));
            });

            $.ajax({
                url: woodynamic_editor.ajax_url,
                type: 'POST',
                data: {
                    action: 'woodynamic_search_products',
                    nonce: woodynamic_editor.nonce,
                    search: searchTerm,
                    exclude: excludedIds
                },
                success: function(response) {
                    if (response.success) {
                        WooDynamicAdmin.displayProductSearchResults(response.data);
                    }
                }
            });
        },

        displayProductSearchResults: function(products) {
            const $results = $('.product-search-results');
            $results.empty();

            if (products.length === 0) {
                $results.append('<div class="no-results">' + woodynamic_editor.strings.no_products_found + '</div>');
                $results.show();
                return;
            }

            products.forEach(function(product) {
                const resultHtml = `
                    <div class="product-search-result" data-product-id="${product.id}">
                        <img src="${product.thumbnail || ''}" alt="">
                        <div class="product-info">
                            <div class="product-name">${product.text}</div>
                            <div class="product-price">${product.price}</div>
                        </div>
                        <button type="button" class="add-to-bundle-btn">${woodynamic_editor.strings.add_to_bundle}</button>
                    </div>
                `;
                $results.append(resultHtml);
            });

            $results.show();
        },

        addProductToBundle: function() {
            const $result = $(this);
            const productId = $result.data('product-id');
            const productName = $result.find('.product-name').text();
            const productPrice = $result.find('.product-price').html();
            const thumbnail = $result.find('img').attr('src');

            // Check if product already exists
            if ($('.bundle-product-item[data-product-id="' + productId + '"]').length > 0) {
                return;
            }

            const productHtml = `
                <div class="bundle-product-item" data-product-id="${productId}">
                    <img src="${thumbnail}" alt="" style="width: 50px; height: 50px;">
                    <div class="product-info">
                        <div class="product-name">${productName}</div>
                        <div class="product-price">${productPrice}</div>
                    </div>
                    <input type="hidden" name="_bundle_products[${productId}][id]" value="${productId}">
                    <button type="button" class="remove-product button">×</button>
                </div>
            `;

            $('.fixed-products-list').append(productHtml);
            $('#product-search').val('').trigger('input');
        },

        removeProductFromBundle: function() {
            $(this).closest('.bundle-product-item').remove();
        },

        saveBundleTemplate: function(e) {
            // This is handled by WordPress save process
            // We can add custom validation here if needed
            const bundleType = $('#bundle_type').val();

            if (bundleType === 'fixed') {
                const productCount = $('.bundle-product-item').length;
                if (productCount === 0) {
                    alert('Please add at least one product to the bundle.');
                    e.preventDefault();
                    return false;
                }
            }
        },

        validateBundleForm: function() {
            let isValid = true;
            const errors = [];

            // Validate bundle type
            const bundleType = $('#bundle_type').val();
            if (!bundleType) {
                errors.push('Please select a bundle type.');
                isValid = false;
            }

            // Validate rules
            const minItems = parseInt($('#_bundle_min_items').val());
            const maxItems = parseInt($('#_bundle_max_items').val());

            if (minItems < 1) {
                errors.push('Minimum items must be at least 1.');
                isValid = false;
            }

            if (maxItems < minItems) {
                errors.push('Maximum items must be greater than or equal to minimum items.');
                isValid = false;
            }

            // Validate pricing
            const pricingType = $('#pricing_type').val();
            if (pricingType !== 'tiered') {
                const discountValue = parseFloat($('#discount_value').val());
                if (discountValue < 0) {
                    errors.push('Discount value cannot be negative.');
                    isValid = false;
                }

                if (pricingType === 'percentage' && discountValue > 100) {
                    errors.push('Percentage discount cannot exceed 100%.');
                    isValid = false;
                }
            }

            // Display errors
            if (!isValid) {
                alert('Please fix the following errors:\n\n' + errors.join('\n'));
            }

            return isValid;
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        WooDynamicAdmin.init();
    });

})(jQuery);
