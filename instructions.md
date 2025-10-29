# WooDynamic Bundles - Development Instructions

## Project Context

WooDynamic Bundles is a WooCommerce plugin that allows customers to create dynamic product bundles with live pricing updates. The plugin must achieve 10/10 compliance with WBCom development standards and provide highly customizable, extendable functionality.

## Key Requirements

### 1. Function Naming
- All functions must be prefixed with `woodynamic_`
- Example: `woodynamic_calculate_bundle_price()`, `woodynamic_validate_bundle()`

### 2. Security Standards
- All inputs sanitized using appropriate WordPress functions
- All outputs escaped before display
- Nonces verified on all forms and AJAX requests
- User capabilities checked before actions
- SQL queries use prepared statements

### 3. Code Quality
- Follow WordPress Coding Standards (PHPCS compliant)
- All files under 1000 lines
- Proper PHPDoc documentation for all public methods
- Type hints used where possible (PHP 7.4+)
- No inline JavaScript or CSS

### 4. Customizability
- Extensive use of `apply_filters()` and `do_action()` hooks
- Template files overrideable by themes
- Settings stored in options with proper sanitization
- Modular architecture for easy extension

### 5. Performance
- Object caching implemented for expensive operations
- Transients used for temporary data storage
- Assets minified and conditionally loaded
- Database queries optimized with proper indexing

### 6. Internationalization
- All user-facing strings wrapped in translation functions
- Text domain: `woodynamic-bundles`
- POT file maintained for translations

## File Structure Standards

```
woodynamic-bundles/
├── woodynamic-bundles.php          # Main plugin file (<200 lines)
├── includes/
│   ├── class-activator.php         # Activation logic
│   ├── class-deactivator.php       # Deactivation logic
│   ├── class-loader.php            # Hook management
│   ├── class-main.php              # Main orchestrator (<300 lines)
│   └── core/                       # Core business logic
├── admin/                          # Admin interface
├── public/                         # Frontend functionality
├── api/                            # REST API and AJAX
├── assets/                         # Static assets
├── languages/                      # Translation files
├── README.md                       # Documentation
├── Guide.md                        # User guide
├── AITrainingGuide.md             # Developer guide
├── Enhancement.md                  # Roadmap
└── uninstall.php                   # Cleanup
```

## Development Workflow

### 1. Code Standards
- Run PHPCS on all commits: `phpcs --standard=WordPress wp-content/plugins/woodynamic-bundles/`
- Fix all errors and warnings before committing
- Use consistent indentation (spaces, not tabs)
- Line length limited to 120 characters

### 2. Testing
- Write unit tests for core classes
- Test all user workflows manually
- Verify compatibility with WordPress 6.0+ and WooCommerce 7.0+
- Test on multiple PHP versions (7.4, 8.0, 8.1)

### 3. Documentation
- Update README.md for any new features
- Maintain changelog in README.md
- Update API documentation for new endpoints
- Keep user guide current

### 4. Security
- Never store sensitive data in plain text
- Use WordPress core functions for all operations
- Validate all external inputs
- Implement rate limiting for expensive operations

### 5. Performance
- Profile code for bottlenecks
- Implement caching where appropriate
- Minimize database queries
- Use lazy loading for large datasets

## Hooks Reference

### Action Hooks
- `woodynamic_bundles_activated` - Plugin activation
- `woodynamic_bundles_deactivated` - Plugin deactivation
- `woodynamic_bundle_saved` - Bundle configuration saved
- `woodynamic_bundle_added_to_cart` - Bundle added to cart
- `woodynamic_before_bundle_builder` - Before bundle display
- `woodynamic_after_bundle_builder` - After bundle display

### Filter Hooks
- `woodynamic_bundle_calculated_price` - Modify calculated prices
- `woodynamic_bundle_discount_amount` - Modify discount amounts
- `woodynamic_bundle_validation` - Modify validation rules
- `woodynamic_bundle_builder_html` - Modify bundle HTML
- `woodynamic_bundle_product_item_html` - Modify product item HTML

## Database Schema

### Tables
- `{$wpdb->prefix}woodynamic_saved_bundles` - User saved bundles

### Meta Keys
- `_bundle_type` - Bundle type (fixed/flexible/mixed)
- `_bundle_rules` - Validation rules array
- `_bundle_pricing` - Pricing configuration array
- `_bundle_products` - Selected products array
- `_bundle_status` - Active/inactive status
- `_bundle_display_settings` - Display preferences array

## API Standards

### REST Endpoints
- Base: `/wp-json/woodynamic/v1/`
- All endpoints return consistent JSON structure
- Proper HTTP status codes
- Comprehensive error messages

### AJAX Actions
- All actions prefixed with `woodynamic_`
- Nonce verification required
- Sanitized inputs and escaped outputs
- Consistent response format

## Asset Management

### CSS
- Scoped with `.woodynamic-` prefix
- Mobile-responsive with breakpoints
- Minified for production
- No !important declarations

### JavaScript
- Uses jQuery (WordPress standard)
- Namespaced functions
- Event delegation for dynamic content
- Error handling with try/catch

## Error Handling

### PHP
```php
try {
    // Operation
} catch (Exception $e) {
    if (WP_DEBUG) {
        error_log('WooDynamic: ' . $e->getMessage());
    }
    return new WP_Error('operation_failed', __('Operation failed', 'woodynamic-bundles'));
}
```

### JavaScript
```javascript
try {
    // Operation
} catch (error) {
    console.error('WooDynamic:', error);
    // Handle error gracefully
}
```

## Version Control

### Commit Messages
- Use imperative mood: "Add feature" not "Added feature"
- Reference issue numbers: "Fix #123 bundle validation"
- Keep first line under 50 characters
- Add detailed description for complex changes

### Branching
- `main` - Production code
- `develop` - Development integration
- `feature/*` - New features
- `bugfix/*` - Bug fixes
- `hotfix/*` - Critical fixes

## Deployment

### Release Process
1. Update version numbers in all files
2. Update changelog
3. Run full test suite
4. Create release branch
5. Tag release
6. Deploy to WordPress.org
7. Update documentation

### Rollback Plan
- Keep previous versions available
- Database migration reversals
- Clear cache and transients
- User communication plan

## Support

### Issue Tracking
- Use GitHub issues for bugs and features
- Include system information in bug reports
- Provide steps to reproduce
- Attach relevant screenshots/logs

### Response Times
- Critical bugs: <4 hours
- Major bugs: <24 hours
- Feature requests: <1 week
- General inquiries: <48 hours

## Quality Assurance

### Code Review Checklist
- [ ] PHPCS standards met
- [ ] Unit tests written and passing
- [ ] Documentation updated
- [ ] Security review completed
- [ ] Performance impact assessed
- [ ] Cross-browser testing done
- [ ] Accessibility standards met

### Pre-Release Checklist
- [ ] All tests pass
- [ ] Documentation complete
- [ ] Translation files updated
- [ ] Assets minified
- [ ] Version numbers consistent
- [ ] Changelog written
- [ ] Backup/rollback procedures tested

---

*These instructions ensure WooDynamic Bundles maintains high quality and follows best practices. All team members should review and follow these guidelines.*
