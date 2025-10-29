# WooDynamic Bundles - Developer Guide

## Overview

WooDynamic Bundles is a comprehensive WooCommerce extension that enables dynamic product bundling with live pricing. This guide provides technical details for developers extending or maintaining the plugin.

## Architecture

### File Structure

```
woodynamic-bundles/
├── woodynamic-bundles.php          # Main plugin file
├── includes/                       # Core classes
│   ├── class-activator.php         # Plugin activation
│   ├── class-deactivator.php       # Plugin deactivation
│   ├── class-loader.php            # Hook/action loader
│   ├── class-main.php              # Main orchestrator
│   └── core/                       # Core functionality
│       ├── class-cpt-bundle-template.php    # Custom post type
│       ├── class-bundle-calculator.php      # Price calculations
│       ├── class-discount-engine.php        # Cart discounts
│       └── class-bundle-session.php         # Saved bundles
├── admin/                          # Admin interface
│   ├── class-admin-menu.php        # Menu registration
│   ├── class-meta-boxes.php        # Meta boxes
│   ├── class-bundle-template-editor.php    # Editor logic
│   └── views/                      # Admin templates
├── public/                         # Frontend
│   ├── class-frontend.php          # Frontend init
│   ├── class-shortcodes.php        # Shortcode handlers
│   ├── class-cart-integration.php  # Cart logic
│   └── templates/                  # Frontend templates
├── api/                            # API layer
│   ├── class-rest-controller.php   # REST API
│   ├── class-ajax-handler.php      # AJAX handlers
│   └── class-bundle-endpoints.php  # Additional endpoints
├── assets/                         # Static assets
│   ├── css/
│   │   ├── admin.css
│   │   └── frontend.css
│   └── js/
│       ├── admin-bundle-builder.js
│       ├── frontend-bundle-selector.js
│       └── cart-integration.js
├── languages/                      # Translations
├── templates/                      # Override templates
├── README.md                       # User documentation
├── Guide.md                        # User guide
├── AITrainingGuide.md             # This file
└── uninstall.php                   # Cleanup script
```

### Class Hierarchy

- `Main`: Entry point, initializes all components
- `Loader`: Manages WordPress hooks and actions
- Core classes handle business logic
- Admin/Public classes handle interfaces
- API classes handle external communications

## Key Classes

### Bundle_Calculator

Handles all pricing calculations:

```php
// Calculate bundle price
$price_data = Bundle_Calculator::woodynamic_calculate_bundle_price($config, $products);

// Validate bundle rules
$validation = Bundle_Calculator::woodynamic_validate_bundle($config, $products);

// Get pricing breakdown
$breakdown = Bundle_Calculator::woodynamic_get_pricing_breakdown($config, $products);
```

### Discount_Engine

Manages cart discounts:

```php
// Initialize discount hooks
Discount_Engine::woodynamic_init();

// Apply to cart item
$cart_item = Discount_Engine::woodynamic_apply_bundle_discount($cart_item_data);
```

### CPT_Bundle_Template

Custom post type management:

```php
// Get bundle data
$bundle = CPT_Bundle_Template::woodynamic_get_bundle_data($post_id);

// Register post type
CPT_Bundle_Template::woodynamic_register_post_type();
```

## Hooks & Filters

### Action Hooks

```php
// Plugin lifecycle
do_action('woodynamic_bundles_activated');
do_action('woodynamic_bundles_deactivated');

// Bundle operations
do_action('woodynamic_bundle_saved', $bundle_id, $user_id, $data);
do_action('woodynamic_bundle_added_to_cart', $bundle_id, $products, $group_key);
do_action('woodynamic_bundle_removed_from_cart', $group_key);

// UI rendering
do_action('woodynamic_before_bundle_builder', $bundle_id);
do_action('woodynamic_after_bundle_builder', $bundle_id);
```

### Filter Hooks

```php
// Calculations
$price_data = apply_filters('woodynamic_bundle_calculated_price', $price_data, $config, $products);
$discount = apply_filters('woodynamic_bundle_discount_amount', $discount, $config, $subtotal);

// Display
$html = apply_filters('woodynamic_bundle_builder_html', $html, $bundle_id);
$html = apply_filters('woodynamic_bundle_product_item_html', $html, $product, $bundle_id);
$html = apply_filters('woodynamic_bundle_summary_html', $html, $bundle_data);

// Validation
$validation = apply_filters('woodynamic_bundle_validation', $validation, $config, $products);
```

## Database Schema

### Custom Tables

```sql
-- Saved bundles
CREATE TABLE {$wpdb->prefix}woodynamic_saved_bundles (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT(20) UNSIGNED NOT NULL,
    bundle_template_id BIGINT(20) UNSIGNED NOT NULL,
    bundle_name VARCHAR(255) DEFAULT '',
    bundle_data LONGTEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX user_id_idx (user_id),
    INDEX bundle_template_id_idx (bundle_template_id)
);
```

### Post Meta Keys

Bundle templates store configuration in post meta:

- `_bundle_type`: 'fixed', 'flexible', 'mixed'
- `_bundle_rules`: Array of validation rules
- `_bundle_pricing`: Pricing configuration
- `_bundle_products`: Array of selected products
- `_bundle_status`: 'active', 'inactive'
- `_bundle_display_settings`: Display preferences

### Cart/Order Meta

- `_bundle_id`: Bundle template ID
- `_bundle_group_key`: Unique group identifier
- `_bundle_discount`: Applied discount amount
- `_bundle_original_price`: Original item price

## API Endpoints

### REST API

Base: `/wp-json/woodynamic/v1/`

#### Public Endpoints

- `GET /bundles` - List bundles
- `GET /bundles/{id}` - Get bundle details
- `POST /bundles/{id}/calculate` - Calculate pricing
- `POST /bundles/{id}/validate` - Validate configuration
- `POST /bundles/{id}/add-to-cart` - Add to cart

#### Authenticated Endpoints

- `GET /saved-bundles` - Get user's saved bundles
- `POST /saved-bundles` - Save bundle
- `DELETE /saved-bundles/{id}` - Delete saved bundle

### AJAX Actions

#### Admin

- `woodynamic_save_bundle_template`
- `woodynamic_search_products`

#### Frontend

- `woodynamic_calculate_bundle`
- `woodynamic_add_bundle_to_cart`
- `woodynamic_save_bundle_config`
- `woodynamic_load_saved_bundles`

## JavaScript API

### Frontend Events

```javascript
// Listen for bundle updates
jQuery(document).on('woodynamic_bundle_updated', function(event, data) {
    console.log('Bundle changed:', data);
});

// Bundle added to cart
jQuery(document).on('woodynamic_bundle_added_to_cart', function(event, data) {
    console.log('Added to cart:', data);
});
```

### Admin Functions

```javascript
// Access bundle editor
const editor = window.woodynamic_editor;

// Access frontend functionality
const frontend = window.woodynamic_frontend;
```

## Template Overrides

Override templates by copying to your theme:

```php
// Original location
wp-content/plugins/woodynamic-bundles/public/templates/bundle-builder.php

// Theme override location
wp-content/themes/your-theme/woodynamic-bundles/bundle-builder.php
```

Available templates:
- `bundle-builder.php` - Main bundle interface
- `bundle-item.php` - Individual product display
- `bundle-summary.php` - Pricing summary

## Customization Examples

### Custom Pricing Logic

```php
add_filter('woodynamic_bundle_calculated_price', function($price_data, $config, $products) {
    // Custom pricing logic
    if ($config['type'] === 'custom') {
        $price_data['total'] = $price_data['subtotal'] * 0.8; // 20% discount
    }
    return $price_data;
}, 10, 3);
```

### Custom Validation

```php
add_filter('woodynamic_bundle_validation', function($validation, $config, $products) {
    // Custom validation rules
    $total_qty = array_sum($products);
    if ($total_qty > 10) {
        $validation['valid'] = false;
        $validation['errors'][] = 'Maximum 10 items allowed';
    }
    return $validation;
}, 10, 3);
```

### Custom Bundle Display

```php
add_filter('woodynamic_bundle_builder_html', function($html, $bundle_id) {
    // Add custom content before bundle
    $html = '<div class="custom-notice">Custom bundle notice</div>' . $html;
    return $html;
}, 10, 2);
```

## Performance Considerations

### Caching

Bundle data is cached using WordPress transients:

```php
// Cache key pattern
$cache_key = 'woodynamic_bundle_' . $bundle_id;

// Cache duration (seconds)
$cache_duration = get_option('woodynamic_cache_duration', 3600);
```

### Database Optimization

- Indexed columns for fast queries
- Prepared statements for security
- Minimal data storage in sessions

### Asset Optimization

- Minified CSS/JS in production
- Conditional loading
- Lazy loading of product data

## Security Measures

### Input Validation

All inputs are validated and sanitized:

```php
// Sanitize bundle rules
$rules = array(
    'min_items' => absint($_POST['min_items']),
    'max_items' => absint($_POST['max_items']),
    'allow_duplicates' => isset($_POST['allow_duplicates']) ? 'yes' : 'no',
);
```

### Nonce Verification

All forms and AJAX requests use nonces:

```php
// Verify nonce
if (!wp_verify_nonce($_POST['nonce'], 'woodynamic_frontend_nonce')) {
    wp_die('Security check failed');
}
```

### Capability Checks

User permissions are verified:

```php
// Check admin access
if (!current_user_can('manage_woocommerce')) {
    wp_die('Insufficient permissions');
}
```

## Testing

### Unit Tests

Core classes include PHPUnit tests:

```bash
# Run tests
phpunit tests/

# Test specific class
phpunit tests/test-bundle-calculator.php
```

### Integration Tests

Test full workflows:

- Bundle creation and editing
- Frontend bundle building
- Cart integration
- Order processing

### Manual Testing Checklist

- [ ] Create fixed/flexible/mixed bundles
- [ ] Test pricing calculations
- [ ] Verify cart integration
- [ ] Check mobile responsiveness
- [ ] Test with variable products
- [ ] Validate stock checking
- [ ] Test saved bundles functionality

## Deployment

### Build Process

```bash
# Install dependencies
composer install
npm install

# Run tests
phpunit
npm test

# Build assets
npm run build

# Create release
# (Follow WordPress.org guidelines)
```

### Version Control

- Use semantic versioning (MAJOR.MINOR.PATCH)
- Maintain changelog
- Tag releases in git

### Update Process

```php
// Version check
$current_version = get_option('woodynamic_bundles_version');
if (version_compare($current_version, WOODYNAMIC_BUNDLES_VERSION, '<')) {
    // Run update routines
    woodynamic_bundles_update_routine();
}
```

## Support & Maintenance

### Error Handling

```php
try {
    // Bundle operation
    $result = woodynamic_calculate_bundle_price($config, $products);
} catch (Exception $e) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('WooDynamic Bundles Error: ' . $e->getMessage());
    }
    return new WP_Error('bundle_error', __('Unable to process bundle.', 'woodynamic-bundles'));
}
```

### Logging

Important events are logged:

```php
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('WooDynamic: Bundle saved - ID: ' . $bundle_id);
}
```

### Monitoring

Track key metrics:

- Bundle creation rate
- Conversion rates
- Error rates
- Performance metrics

## Contributing

### Code Standards

- Follow WordPress Coding Standards
- Use PHPDoc for all functions
- Prefix all functions with `woodynamic_`
- Test all changes

### Pull Request Process

1. Fork the repository
2. Create a feature branch
3. Make changes with tests
4. Update documentation
5. Submit pull request

### Release Checklist

- [ ] All tests pass
- [ ] Code standards check
- [ ] Documentation updated
- [ ] Version numbers updated
- [ ] Changelog written
- [ ] Assets minified
- [ ] Translation files updated

---

*This guide is for developers working with WooDynamic Bundles. For user documentation, see Guide.md.*
