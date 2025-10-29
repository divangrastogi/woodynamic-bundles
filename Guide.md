# WooDynamic Bundles - User Guide

## Introduction

WooDynamic Bundles allows your customers to create custom product bundles with live pricing updates. This guide will help you set up and manage the plugin effectively.

## Installation

1. Download the plugin from WordPress.org or your purchase confirmation
2. Go to **WordPress Admin > Plugins > Add New**
3. Click **Upload Plugin** and select the downloaded zip file
4. Click **Install Now** then **Activate**

## Getting Started

### Step 1: Create Your First Bundle Template

1. Navigate to **WooCommerce > Bundle Templates**
2. Click **Add New**
3. Give your bundle a title (e.g., "Build Your Perfect Skincare Set")
4. Add a description explaining what the bundle is for

### Step 2: Configure Bundle Rules

In the **Bundle Configuration** meta box:

- **Bundle Type**: Choose how customers can interact with products
  - **Fixed**: Pre-selected products that can't be changed
  - **Flexible**: Customers choose from allowed categories
  - **Mixed**: Combination of fixed and flexible products

- **Item Limits**: Set minimum and maximum products allowed
- **Category Restrictions**: Limit products to specific categories (optional)
- **Allow Duplicates**: Let customers add the same product multiple times

### Step 3: Set Up Pricing

In the **Pricing Rules** meta box:

- **Discount Type**: 
  - **Percentage**: Reduce total by a percentage
  - **Fixed Amount**: Subtract a fixed dollar amount
  - **Tiered Pricing**: Different discounts based on quantity

- **Discount Value**: Enter the discount amount
- **Tiered Rules**: For tiered pricing, add quantity thresholds and their discounts

### Step 4: Add Products (for Fixed Bundles)

In the **Product Selection** meta box:

- Use the search box to find products
- Click products to add them to the bundle
- Drag to reorder products if needed

### Step 5: Configure Display Settings

In the **Display Settings** meta box:

- Choose whether to show individual product prices
- Decide if savings should be displayed
- Select grid or list layout

### Step 6: Publish and Display

1. Set the bundle status to **Active**
2. Click **Publish**
3. Copy the bundle ID from the URL
4. Add the shortcode to any page: `[woodynamic_bundle id="123"]`

## Advanced Configuration

### Plugin Settings

Go to **WooCommerce > Bundle Settings** to configure:

- **General Settings**: Enable/disable bundles, set defaults
- **Display Settings**: Control what customers see
- **Cart Settings**: How bundles appear in cart
- **Performance Settings**: Caching and optimization

### Customizing Bundle Templates

You can override the default templates by copying files from:
`wp-content/plugins/woodynamic-bundles/public/templates/`

To your theme:
`wp-content/themes/your-theme/woodynamic-bundles/`

### Using Shortcode Parameters

```
[woodynamic_bundle id="123" layout="list" columns="2"]
```

- `id`: Bundle template ID (required)
- `layout`: "grid" or "list"
- `columns`: Number of columns (1-6)
- `show_title`: "yes" or "no"
- `show_description`: "yes" or "no"

## Managing Bundles

### Editing Existing Bundles

1. Go to **WooCommerce > Bundle Templates**
2. Click on any bundle title to edit
3. Make your changes and click **Update**

### Organizing Bundles

- Use categories to group similar bundles
- Add tags for additional filtering
- Set bundle status to control visibility

### Monitoring Performance

Check the **Analytics** tab (if available) to see:
- Most popular bundles
- Conversion rates
- Average order values

## Customer Experience

### How Customers Use Bundles

1. **Select Products**: Customers browse available products
2. **Choose Quantities**: Adjust how many of each product they want
3. **See Live Pricing**: Prices update automatically as they make changes
4. **Add to Cart**: Bundles are added as grouped items
5. **Save for Later**: Customers can save incomplete bundles (if enabled)

### Cart and Checkout

- Bundled items appear grouped together
- Bundle name and savings are clearly displayed
- Individual items can be removed (based on settings)
- Discounts are applied automatically

## Troubleshooting

### Common Issues

**Bundle not displaying:**
- Check that the bundle is published and active
- Verify the shortcode ID is correct
- Ensure WooCommerce is active

**Prices not updating:**
- Check browser developer console for errors
- Verify AJAX is working
- Clear any caching plugins

**Products not appearing:**
- Check category restrictions
- Ensure products are in stock and published
- Verify product visibility settings

### Getting Help

- Check the **System Status** page for compatibility issues
- Enable **WP_DEBUG** for detailed error messages
- Contact support with your system report

## Best Practices

### Bundle Design

- Keep bundle names clear and descriptive
- Use high-quality product images
- Set reasonable minimum/maximum limits
- Test bundles on mobile devices

### Pricing Strategy

- Offer meaningful discounts (15-30% typically works well)
- Use tiered pricing to encourage larger bundles
- Consider product margins when setting discounts

### Performance

- Limit bundles to 20-30 products maximum
- Use caching if you have many bundles
- Optimize product images

### Customer Support

- Monitor abandoned bundles
- Provide clear instructions
- Test the full purchase flow regularly

## API Usage

### REST API

Access bundle data programmatically:

```javascript
// Get all bundles
fetch('/wp-json/woodynamic/v1/bundles')
  .then(response => response.json())
  .then(data => console.log(data));

// Calculate bundle price
fetch('/wp-json/woodynamic/v1/bundles/123/calculate', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    products: [
      {id: 456, qty: 2},
      {id: 789, qty: 1}
    ]
  })
});
```

### JavaScript Events

Listen for bundle events:

```javascript
jQuery(document).on('woodynamic_bundle_updated', function(event, data) {
  console.log('Bundle updated:', data);
});

jQuery(document).on('woodynamic_bundle_added_to_cart', function(event, data) {
  console.log('Bundle added to cart:', data);
});
```

## Security

The plugin includes multiple security measures:

- All forms use WordPress nonces
- User inputs are sanitized
- Database queries use prepared statements
- File permissions are properly set

## Updates

The plugin will notify you of available updates in the WordPress admin. Always backup your site before updating.

## Uninstalling

To completely remove the plugin:

1. Go to **Plugins** and deactivate WooDynamic Bundles
2. Click **Delete**
3. If you want to keep bundle data, set the option in settings first

---

*This guide covers the basic usage of WooDynamic Bundles. For advanced customization, see the developer documentation.*
