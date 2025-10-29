/**
 * Frontend Bundle Selector JavaScript
 *
 * Handles frontend bundle builder interactions
 */

(function ($) {
  "use strict";

  const WooDynamicBundle = {
    init: function () {
      this.bindEvents();
      this.initBundleBuilder();
      this.initThemeToggle();
      this.initToastContainer();
    },

    // Utility functions
    showLoading: function ($element, text = "") {
      $element.addClass("bundle-loading");
      if (text) {
        $element.find(".loading-text").remove();
        $element.append(
          `<div class="loading-text"><span class="spinner"></span>${text}</div>`,
        );
      }
    },

    hideLoading: function ($element) {
      $element.removeClass("bundle-loading");
      $element.find(".loading-text").remove();
    },

    showToast: function (message, type = "success", duration = 3000) {
      const $toast = $(`
                <div class="toast ${type}" role="alert">
                    <span class="toast-icon">${type === "success" ? "✅" : type === "error" ? "❌" : "⚠️"}</span>
                    <span class="toast-message">${message}</span>
                    <button class="toast-close" aria-label="Close notification">&times;</button>
                </div>
            `);

      $(".toast-container").append($toast);

      // Animate in
      setTimeout(() => $toast.addClass("show"), 10);

      // Auto remove
      const removeToast = () => {
        $toast.removeClass("show");
        setTimeout(() => $toast.remove(), 300);
      };

      if (duration > 0) {
        setTimeout(removeToast, duration);
      }

      // Manual close
      $toast.find(".toast-close").on("click", removeToast);
    },

    updateProgress: function (currentItems, minItems) {
      const $progress = $(".bundle-progress");
      const $progressFill = $(".progress-fill");
      const $progressText = $(".progress-text");

      if (currentItems >= minItems) {
        $progress.hide();
        return;
      }

      const percentage = (currentItems / minItems) * 100;
      $progressFill.css("width", `${percentage}%`);
      $progressText.text(
        `Add ${minItems - currentItems} more item${minItems - currentItems === 1 ? "" : "s"} to complete your bundle`,
      );
      $progress.show();
    },

    initThemeToggle: function () {
      const $toggle = $(
        '<button class="theme-toggle" aria-label="Toggle theme"></button>',
      );
      $("body").append($toggle);

      $toggle.on("click", function () {
        const currentTheme =
          document.documentElement.getAttribute("data-theme");
        const newTheme = currentTheme === "dark" ? "light" : "dark";

        document.documentElement.setAttribute("data-theme", newTheme);
        localStorage.setItem("woodynamic-theme", newTheme);

        WooDynamicBundle.showToast(
          `Switched to ${newTheme} mode`,
          "success",
          1500,
        );
      });

      // Load saved theme
      const savedTheme = localStorage.getItem("woodynamic-theme") || "light";
      document.documentElement.setAttribute("data-theme", savedTheme);
    },

    initToastContainer: function () {
      if (!$(".toast-container").length) {
        $("body").append(
          '<div class="toast-container" aria-live="polite" aria-atomic="true"></div>',
        );
      }
    },

    bindEvents: function () {
      // Quantity selectors
      $(document).on("click", ".qty-decrease", this.decreaseQuantity);
      $(document).on("click", ".qty-increase", this.increaseQuantity);
      $(document).on("input", ".qty-input", this.updateQuantity);

      // Add to bundle
      $(document).on("click", ".add-to-bundle", this.addToBundle);

      // Remove from bundle
      $(document).on("click", ".remove-item", this.removeFromBundle);

      // Add bundle to cart
      $(document).on("click", ".add-bundle-to-cart", this.addBundleToCart);

      // Save bundle
      $(document).on("click", ".save-bundle", this.saveBundle);

      // Bundle name input
      $(document).on("input", "#bundle-name", this.updateBundleName);
    },

    initBundleBuilder: function () {
      const $wrappers = $(".woodynamic-bundle-wrapper");

      $wrappers.each(function () {
        const $wrapper = $(this);
        const bundleId = $wrapper.data("bundle-id");

        // Load initial bundle configuration
        WooDynamicBundle.loadBundleConfig($wrapper, bundleId);
      });
    },

    loadBundleConfig: function ($wrapper, bundleId) {
      // This could load additional config via AJAX if needed
      // For now, we use the data attributes
    },

    decreaseQuantity: function () {
      const $input = $(this).siblings(".qty-input");
      const currentVal = parseInt($input.val());
      const min = parseInt($input.attr("min")) || 0;

      if (currentVal > min) {
        $input.val(currentVal - 1).trigger("input");
      }
    },

    increaseQuantity: function () {
      const $input = $(this).siblings(".qty-input");
      const currentVal = parseInt($input.val());
      const max = parseInt($input.attr("max")) || 999;

      if (currentVal < max) {
        $input.val(currentVal + 1).trigger("input");
      }
    },

    updateQuantity: function () {
      const $input = $(this);
      let value = parseInt($input.val());
      const min = parseInt($input.attr("min")) || 0;
      const max = parseInt($input.attr("max")) || 999;

      // Validate input
      if (isNaN(value) || value < min) {
        value = min;
      } else if (value > max) {
        value = max;
      }

      $input.val(value);

      // Update bundle if this product is selected
      const $productItem = $input.closest(".bundle-product-item");
      const productId = $productItem.data("product-id");

      WooDynamicBundle.updateBundleProduct(productId, value);
    },

    addToBundle: function () {
      const $button = $(this);
      const $productItem = $button.closest(".bundle-product-item");
      const productId = $productItem.data("product-id");
      const quantity = parseInt($productItem.find(".qty-input").val()) || 1;

      // Show loading state
      WooDynamicBundle.showLoading($button, "Adding...");

      // Add visual feedback
      $productItem.addClass("selected");

      // Update bundle
      WooDynamicBundle.updateBundleProduct(productId, quantity);

      // Show success feedback
      setTimeout(() => {
        WooDynamicBundle.hideLoading($button);
        WooDynamicBundle.showToast("Product added to bundle!", "success", 2000);
      }, 300);
    },

    updateBundleProduct: function (productId, quantity) {
      const $wrapper = $(".woodynamic-bundle-wrapper");
      const bundleId = $wrapper.data("bundle-id");

      // Get current bundle products
      let bundleProducts = this.getBundleProducts();

      if (quantity > 0) {
        bundleProducts[productId] = quantity;
      } else {
        delete bundleProducts[productId];
      }

      // Update UI
      this.updateBundleUI(bundleProducts);

      // Recalculate prices
      this.calculateBundlePrice(bundleId, bundleProducts);
    },

    getBundleProducts: function () {
      const bundleProducts = {};

      $(".selected-item").each(function () {
        const productId = $(this).data("product-id");
        const quantity = parseInt(
          $(this).find(".item-qty").text().replace("×", ""),
        );
        bundleProducts[productId] = quantity;
      });

      return bundleProducts;
    },

    updateBundleUI: function (bundleProducts) {
      const $selectedItems = $(".selected-items");
      $selectedItems.empty();

      if (Object.keys(bundleProducts).length === 0) {
        $selectedItems.append(
          '<p class="no-items">' +
            woodynamic_frontend.strings.select_products +
            "</p>",
        );
        return;
      }

      Object.keys(bundleProducts).forEach((productId) => {
        const quantity = bundleProducts[productId];
        const $productItem = $(
          '.bundle-product-item[data-product-id="' + productId + '"]',
        );
        const productName = $productItem.find(".product-name").text();
        const productPrice = $productItem.find(".product-price").text();

        const itemHtml = `
                    <div class="selected-item" data-product-id="${productId}">
                        <span class="item-name">${productName}</span>
                        <span class="item-qty">×${quantity}</span>
                        <span class="item-price">${productPrice}</span>
                        <button class="remove-item" type="button">×</button>
                    </div>
                `;

        $selectedItems.append(itemHtml);
      });
    },

    removeFromBundle: function () {
      const $item = $(this).closest(".selected-item");
      const productId = $item.data("product-id");

      // Reset quantity input
      $(
        '.bundle-product-item[data-product-id="' + productId + '"] .qty-input',
      ).val(0);

      // Update bundle
      WooDynamicBundle.updateBundleProduct(productId, 0);
    },

    calculateBundlePrice: function (bundleId, products) {
      const $wrapper = $(".woodynamic-bundle-wrapper");

      $wrapper.addClass("bundle-loading");

      $.ajax({
        url: woodynamic_frontend.ajax_url,
        type: "POST",
        data: {
          action: "woodynamic_calculate_bundle",
          nonce: woodynamic_frontend.nonce,
          bundle_id: bundleId,
          products: products,
        },
        success: function (response) {
          if (response.success) {
            WooDynamicBundle.updatePricingDisplay(response.data.calculation);
            WooDynamicBundle.updateValidationStatus(response.data.validation);
          } else {
            WooDynamicBundle.showBundleError(
              response.data.message || "Calculation failed",
            );
          }
        },
        error: function () {
          WooDynamicBundle.showBundleError("Network error occurred");
        },
        complete: function () {
          $wrapper.removeClass("bundle-loading");
        },
      });
    },

    updatePricingDisplay: function (calculation) {
      $(".subtotal .amount").html(
        calculation.subtotal_formatted || wc_price(calculation.subtotal),
      );
      $(".total .amount").html(
        calculation.total_formatted || wc_price(calculation.total),
      );

      if (calculation.discount > 0) {
        $(".discount .amount").html(
          "-" +
            (calculation.discount_formatted || wc_price(calculation.discount)),
        );
        $(".discount").show();

        if (calculation.savings_percentage > 0) {
          $(".savings .amount").html(
            wc_price(calculation.discount) +
              " (" +
              calculation.savings_percentage +
              "%)",
          );
          $(".savings").show();
        }
      } else {
        $(".discount, .savings").hide();
      }
    },

    updateValidationStatus: function (validation) {
      const $addToCartBtn = $(".add-bundle-to-cart");
      const $errors = $(".bundle-errors");

      $errors.empty().hide();

      if (validation.valid) {
        $addToCartBtn.prop("disabled", false);
      } else {
        $addToCartBtn.prop("disabled", true);

        if (validation.errors && validation.errors.length > 0) {
          const errorHtml =
            "<ul>" +
            validation.errors
              .map((error) => "<li>" + error + "</li>")
              .join("") +
            "</ul>";
          $errors.html(errorHtml).show();
        }
      }
    },

    addBundleToCart: function () {
      const $button = $(this);
      const $wrapper = $button.closest(".woodynamic-bundle-wrapper");
      const bundleId = $wrapper.data("bundle-id");
      const products = WooDynamicBundle.getBundleProducts();

      if (Object.keys(products).length === 0) {
        WooDynamicBundle.showToast(
          "Please select at least one product for your bundle.",
          "warning",
        );
        $button.addClass("shake");
        setTimeout(() => $button.removeClass("shake"), 500);
        return;
      }

      // Show loading overlay
      const $overlay = $(
        '<div class="loading-overlay show"><div class="loading-content"><div class="spinner spinner-large"></div><p>Adding bundle to cart...</p></div></div>',
      );
      $("body").append($overlay);

      $button.prop("disabled", true);

      $.ajax({
        url: woodynamic_frontend.ajax_url,
        type: "POST",
        data: {
          action: "woodynamic_add_bundle_to_cart",
          nonce: woodynamic_frontend.nonce,
          bundle_id: bundleId,
          products: products,
        },
        success: function (response) {
          $overlay.remove();

          if (response.success) {
            // Redirect to cart or show success message
            if (woodynamic_frontend.cart_url) {
              window.location.href = woodynamic_frontend.cart_url;
            } else {
              $button.html(woodynamic_frontend.strings.added_to_cart);
              setTimeout(function () {
                $button
                  .prop("disabled", false)
                  .html(woodynamic_frontend.strings.add_to_cart);
              }, 2000);
            }
          } else {
            WooDynamicBundle.showBundleError(
              response.data.message || "Failed to add bundle to cart",
            );
            $button
              .prop("disabled", false)
              .html(woodynamic_frontend.strings.add_to_cart);
          }
        },
        error: function () {
          WooDynamicBundle.showBundleError("Network error occurred");
          $button
            .prop("disabled", false)
            .html(woodynamic_frontend.strings.add_to_cart);
        },
      });
    },

    saveBundle: function () {
      const $button = $(this);
      const $wrapper = $button.closest(".woodynamic-bundle-wrapper");
      const bundleId = $wrapper.data("bundle-id");
      const products = WooDynamicBundle.getBundleProducts();
      const bundleName = $("#bundle-name").val() || "";

      if (Object.keys(products).length === 0) {
        WooDynamicBundle.showBundleError(
          woodynamic_frontend.strings.select_products,
        );
        return;
      }

      $button
        .prop("disabled", true)
        .html(
          '<span class="spinner"></span>' +
            woodynamic_frontend.strings.saving_bundle,
        );

      $.ajax({
        url: woodynamic_frontend.ajax_url,
        type: "POST",
        data: {
          action: "woodynamic_save_bundle_config",
          nonce: woodynamic_frontend.nonce,
          bundle_id: bundleId,
          products: products,
          bundle_name: bundleName,
        },
        success: function (response) {
          if (response.success) {
            $button.html(woodynamic_frontend.strings.bundle_saved);
            setTimeout(function () {
              $button
                .prop("disabled", false)
                .html(woodynamic_frontend.strings.save_bundle);
            }, 2000);
          } else {
            WooDynamicBundle.showBundleError(
              response.data.message || "Failed to save bundle",
            );
            $button
              .prop("disabled", false)
              .html(woodynamic_frontend.strings.save_bundle);
          }
        },
        error: function () {
          WooDynamicBundle.showBundleError("Network error occurred");
          $button
            .prop("disabled", false)
            .html(woodynamic_frontend.strings.save_bundle);
        },
      });
    },

    showBundleError: function (message) {
      const $errors = $(".bundle-errors");
      $errors.html("<p>" + message + "</p>").show();

      // Scroll to errors
      $("html, body").animate(
        {
          scrollTop: $errors.offset().top - 100,
        },
        500,
      );
    },

    updateBundleName: function () {
      // Optional: Update bundle name in real-time
    },
  };

  // Initialize when document is ready
  $(document).ready(function () {
    WooDynamicBundle.init();
  });
})(jQuery);
