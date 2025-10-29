# WooDynamic Bundles

A professional WooCommerce plugin that allows customers to create dynamic product bundles with live pricing updates.

## Features

- **Dynamic Bundle Creation**: Customers can select products and quantities to build custom bundles
- **Live Price Calculation**: Real-time pricing updates as customers modify their bundles
- **Flexible Bundle Types**: Support for fixed, flexible, and mixed bundle configurations
- **Advanced Discount Engine**: Percentage, fixed amount, and tiered pricing discounts
- **Admin Management**: Comprehensive admin interface for creating and managing bundle templates
- **Cart Integration**: Seamless integration with WooCommerce cart and checkout
- **REST API**: Full REST API support for external integrations
- **Save & Resume**: Customers can save bundles and resume later
- **Mobile Responsive**: Fully responsive design for all devices

## Requirements

- WordPress 6.0+
- WooCommerce 7.0+
- PHP 7.4+

## Installation

1. Download the plugin zip file
2. Go to WordPress Admin > Plugins > Add New
3. Click "Upload Plugin" and select the zip file
4. Click "Install Now" and then "Activate"

## Quick Start

### Creating a Bundle Template

1. Go to **WooCommerce > Bundle Templates**
2. Click **Add New**
3. Configure bundle settings:
   - **Bundle Type**: Choose Fixed, Flexible, or Mixed
   - **Item Limits**: Set minimum and maximum items
   - **Pricing Rules**: Configure discounts
   - **Product Selection**: Add products (for fixed bundles) or categories (for flexible bundles)
4. Publish the bundle template

### Displaying Bundles on Your Site

Use the shortcode to display bundle builders on any page:

```
[woodynamic_bundle id="123" layout="grid" columns="3"]
```

**Shortcode Parameters:**
- `id` (required): Bundle template ID
- `layout`: 'grid' or 'list' (default: grid)
- `columns`: Number of columns (default: 3)

## Configuration

### General Settings

- **Enable Bundles**: Toggle bundle functionality
- **Default Layout**: Grid or list view
- **Maximum Saved Bundles**: Per user limit
- **Allow Guest Users**: Enable saving for non-logged users

### Display Settings

- **Show Bundle Badge**: Display bundle indicators on products
- **Show Savings**: Display discount amounts
- **Show Individual Prices**: Show prices for each product

### Cart Settings

- **Group Bundle Items**: Display bundled items together
- **Show Bundle Name**: Display bundle name in cart
- **Allow Individual Removal**: Permit removing single items from bundles

## API Reference

### REST Endpoints

#### GET /wp-json/woodynamic/v1/bundles
List all active bundle templates

**Parameters:**
- `category` (int): Filter by category ID
- `search` (string): Search term
- `per_page` (int): Items per page (default: 10)
- `page` (int): Page number (default: 1)

#### GET /wp-json/woodynamic/v1/bundles/{id}
Get single bundle configuration

#### POST /wp-json/woodynamic/v1/bundles/{id}/calculate
Calculate bundle price

**Body:**
```json
{
  "products": [
    {"id": 123, "qty": 2},
    {"id": 456, "qty": 1}
  ]
}
```

#### POST /wp-json/woodynamic/v1/bundles/{id}/add-to-cart
Add bundle to cart

### AJAX Actions

- `woodynamic_calculate_bundle`: Calculate bundle pricing
- `woodynamic_add_bundle_to_cart`: Add bundle to WooCommerce cart
- `woodynamic_save_bundle_config`: Save bundle configuration

## Hooks & Filters

### Action Hooks

```php
// Admin hooks
do_action('woodynamic_bundles_activated');
do_action('woodynamic_bundle_saved', $bundle_id, $user_id, $bundle_data);
do_action('woodynamic_bundle_added_to_cart', $bundle_id, $products, $group_key);

// Frontend hooks
do_action('woodynamic_before_bundle_builder', $bundle_id);
do_action('woodynamic_after_bundle_builder', $bundle_id);
```

### Filter Hooks

```php
// Calculation filters
apply_filters('woodynamic_bundle_calculated_price', $price_data, $bundle_config, $products);
apply_filters('woodynamic_bundle_discount_amount', $discount, $bundle_config, $subtotal);

// Display filters
apply_filters('woodynamic_bundle_builder_html', $html, $bundle_id);
apply_filters('woodynamic_bundle_product_item_html', $html, $product, $bundle_id);
apply_filters('woodynamic_bundle_summary_html', $html, $bundle_data);
```

## Security Features

- **Nonce Verification**: All forms and AJAX requests use WordPress nonces
- **Input Sanitization**: All user inputs are properly sanitized
- **Capability Checks**: User permissions are verified before actions
- **SQL Injection Prevention**: Prepared statements used for all database queries
- **XSS Protection**: All outputs are escaped appropriately

## Performance Optimizations

- **Object Caching**: Bundle configurations cached for performance
- **Lazy Loading**: Products loaded on demand
- **Minified Assets**: CSS and JS files are optimized
- **Database Indexing**: Proper indexes on custom tables

## Troubleshooting

### Common Issues

**Bundles not displaying:**
- Ensure the bundle template is published and active
- Check shortcode syntax
- Verify WooCommerce is active

**Pricing not updating:**
- Check browser console for JavaScript errors
- Ensure AJAX endpoints are accessible
- Verify bundle configuration is valid

**Cart integration issues:**
- Check WooCommerce cart fragments are working
- Verify bundle products are in stock
- Check for plugin conflicts

### Debug Mode

Enable WordPress debug mode to see detailed error messages:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Development

### File Structure

```
woodynamic-bundles/
├── woodynamic-bundles.php          # Main plugin file
├── includes/                       # Core PHP classes
│   ├── class-activator.php
│   ├── class-deactivator.php
│   ├── class-loader.php
│   ├── class-main.php
│   ├── core/                       # Core functionality
│   └── ...
├── admin/                          # Admin interface
├── public/                         # Frontend functionality
├── api/                            # REST API and AJAX
├── assets/                         # CSS, JS, images
├── languages/                      # Translation files
├── templates/                      # Template files
└── uninstall.php                   # Uninstall script
```

### Coding Standards

This plugin follows WordPress Coding Standards and WooCommerce best practices:

- All functions prefixed with `woodynamic_`
- Proper PHPDoc documentation
- Input sanitization and output escaping
- Secure database queries

### Testing

Run the included test suite:

```bash
phpunit
```

### Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## Changelog

### 1.0.0
- Initial release
- Dynamic bundle creation
- Live pricing calculation
- Admin management interface
- Cart integration
- REST API support

## License

GPL-2.0+

## Support

For support, please use the WordPress.org support forums or contact the plugin author.

## Credits

Developed by WBCom Designs
