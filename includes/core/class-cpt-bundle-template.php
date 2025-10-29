<?php
/**
 * Custom Post Type for Bundle Templates
 *
 * @package WooDynamic\Bundles
 */

namespace WooDynamic\Bundles;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * CPT_Bundle_Template class
 */
class CPT_Bundle_Template {

    /**
     * Post type name
     */
    const POST_TYPE = 'wc_bundle_template';

    /**
     * Initialize the CPT
     */
    public function woodynamic_init() {
        add_action('init', array($this, 'woodynamic_register_post_type'));
        add_action('init', array($this, 'woodynamic_register_taxonomies'));
        add_filter('manage_' . self::POST_TYPE . '_posts_columns', array($this, 'woodynamic_add_columns'));
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', array($this, 'woodynamic_fill_columns'), 10, 2);
        add_filter('post_row_actions', array($this, 'woodynamic_modify_row_actions'), 10, 2);
        add_action('wp_insert_post', array($this, 'woodynamic_set_default_meta'), 10, 3);
    }

    /**
     * Register the custom post type
     */
    public static function woodynamic_register_post_type() {
        $labels = array(
            'name'                  => _x('Bundle Templates', 'Post type general name', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            'singular_name'         => _x('Bundle Template', 'Post type singular name', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            'menu_name'             => _x('Bundle Templates', 'Admin Menu text', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            'name_admin_bar'        => _x('Bundle Template', 'Add New on Toolbar', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            'add_new'               => __('Add New', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            'add_new_item'          => __('Add New Bundle Template', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            'new_item'              => __('New Bundle Template', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            'edit_item'             => __('Edit Bundle Template', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            'view_item'             => __('View Bundle Template', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            'all_items'             => __('All Bundle Templates', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            'search_items'          => __('Search Bundle Templates', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            'parent_item_colon'     => __('Parent Bundle Templates:', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            'not_found'             => __('No bundle templates found.', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            'not_found_in_trash'    => __('No bundle templates found in Trash.', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            'featured_image'        => _x('Bundle Image', 'Overrides the "Featured Image" phrase', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            'set_featured_image'    => _x('Set bundle image', 'Overrides the "Set featured image" phrase', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            'remove_featured_image' => _x('Remove bundle image', 'Overrides the "Remove featured image" phrase', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            'use_featured_image'    => _x('Use as bundle image', 'Overrides the "Use as featured image" phrase', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => false, // Will be added to WooCommerce menu
            'query_var'          => false,
            'rewrite'            => false,
            'capability_type'    => 'product',
            'capabilities'       => array(
                'edit_post'          => 'manage_woocommerce',
                'read_post'          => 'manage_woocommerce',
                'delete_post'        => 'manage_woocommerce',
                'edit_posts'         => 'manage_woocommerce',
                'edit_others_posts'  => 'manage_woocommerce',
                'publish_posts'      => 'manage_woocommerce',
                'read_private_posts' => 'manage_woocommerce',
            ),
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array('title', 'editor', 'thumbnail'),
            'show_in_rest'       => false,
        );

        register_post_type(self::POST_TYPE, $args);
    }

    /**
     * Register taxonomies for bundle templates
     */
    public function woodynamic_register_taxonomies() {
        // Bundle Category
        $category_labels = array(
            'name'              => _x('Bundle Categories', 'taxonomy general name', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            'singular_name'     => _x('Bundle Category', 'taxonomy singular name', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            'search_items'      => __('Search Bundle Categories', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            'all_items'         => __('All Bundle Categories', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            'parent_item'       => __('Parent Bundle Category', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            'parent_item_colon' => __('Parent Bundle Category:', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            'edit_item'         => __('Edit Bundle Category', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            'update_item'       => __('Update Bundle Category', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            'add_new_item'      => __('Add New Bundle Category', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            'new_item_name'     => __('New Bundle Category Name', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            'menu_name'         => __('Bundle Categories', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
        );

        $category_args = array(
            'hierarchical'      => true,
            'labels'            => $category_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => false,
            'rewrite'           => false,
            'show_in_rest'      => false,
        );

        register_taxonomy('bundle_category', array(self::POST_TYPE), $category_args);

        // Bundle Tag
        $tag_labels = array(
            'name'              => _x('Bundle Tags', 'taxonomy general name', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            'singular_name'     => _x('Bundle Tag', 'taxonomy singular name', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            'search_items'      => __('Search Bundle Tags', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            'all_items'         => __('All Bundle Tags', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            'edit_item'         => __('Edit Bundle Tag', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            'update_item'       => __('Update Bundle Tag', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            'add_new_item'      => __('Add New Bundle Tag', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            'new_item_name'     => __('New Bundle Tag Name', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
            'menu_name'         => __('Bundle Tags', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
        );

        $tag_args = array(
            'hierarchical'      => false,
            'labels'            => $tag_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => false,
            'rewrite'           => false,
            'show_in_rest'      => false,
        );

        register_taxonomy('bundle_tag', array(self::POST_TYPE), $tag_args);
    }

    /**
     * Add custom columns to the admin list
     *
     * @param array $columns Existing columns
     * @return array Modified columns
     */
    public function woodynamic_add_columns($columns) {
        $new_columns = array();

        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['bundle_type'] = __('Type', WOODYNAMIC_BUNDLES_TEXT_DOMAIN);
                $new_columns['bundle_status'] = __('Status', WOODYNAMIC_BUNDLES_TEXT_DOMAIN);
                $new_columns['bundle_products'] = __('Products', WOODYNAMIC_BUNDLES_TEXT_DOMAIN);
            }
        }

        return $new_columns;
    }

    /**
     * Fill custom columns with data
     *
     * @param string $column Column name
     * @param int    $post_id Post ID
     */
    public function woodynamic_fill_columns($column, $post_id) {
        switch ($column) {
            case 'bundle_type':
                $type = get_post_meta($post_id, '_bundle_type', true);
                $type_labels = array(
                    'fixed'   => __('Fixed', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
                    'flexible' => __('Flexible', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
                    'mixed'   => __('Mixed', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
                );
                echo isset($type_labels[$type]) ? esc_html($type_labels[$type]) : __('Unknown', WOODYNAMIC_BUNDLES_TEXT_DOMAIN);
                break;

            case 'bundle_status':
                $status = get_post_meta($post_id, '_bundle_status', true);
                $status = $status ?: 'active';
                $status_labels = array(
                    'active'   => '<span style="color: green;">' . __('Active', WOODYNAMIC_BUNDLES_TEXT_DOMAIN) . '</span>',
                    'inactive' => '<span style="color: red;">' . __('Inactive', WOODYNAMIC_BUNDLES_TEXT_DOMAIN) . '</span>',
                );
                echo isset($status_labels[$status]) ? $status_labels[$status] : __('Unknown', WOODYNAMIC_BUNDLES_TEXT_DOMAIN);
                break;

            case 'bundle_products':
                $products = get_post_meta($post_id, '_bundle_products', true);
                if (is_array($products)) {
                    echo esc_html(count($products));
                } else {
                    echo '0';
                }
                break;
        }
    }

    /**
     * Modify row actions in admin list
     *
     * @param array   $actions Existing actions
     * @param WP_Post $post    Post object
     * @return array Modified actions
     */
    public function woodynamic_modify_row_actions($actions, $post) {
        if ($post->post_type === self::POST_TYPE) {
            // Add preview action
            $actions['preview'] = sprintf(
                '<a href="%s" target="_blank">%s</a>',
                esc_url(get_permalink($post->ID)),
                __('Preview', WOODYNAMIC_BUNDLES_TEXT_DOMAIN)
            );
        }

        return $actions;
    }

    /**
     * Get bundle template data
     *
     * @param int $post_id Post ID
     * @return array Bundle data
     */
    public static function woodynamic_get_bundle_data($post_id) {
        return array(
            'id' => $post_id,
            'title' => get_the_title($post_id),
            'description' => get_the_content(null, false, $post_id),
            'type' => get_post_meta($post_id, '_bundle_type', true),
            'rules' => get_post_meta($post_id, '_bundle_rules', true),
            'pricing' => get_post_meta($post_id, '_bundle_pricing', true),
            'products' => get_post_meta($post_id, '_bundle_products', true),
            'status' => get_post_meta($post_id, '_bundle_status', true),
            'display_settings' => get_post_meta($post_id, '_bundle_display_settings', true),
            'thumbnail' => get_the_post_thumbnail_url($post_id, 'full'),
        );
    }

    /**
     * Set default meta for new bundle templates
     *
     * @param int $post_id Post ID
     * @param WP_Post $post Post object
     * @param bool $update Whether this is an update
     */
    public function woodynamic_set_default_meta($post_id, $post, $update) {
        if ($update || $post->post_type !== self::POST_TYPE) {
            return;
        }

        // Set default meta for new bundle templates
        if (!get_post_meta($post_id, '_bundle_type', true)) {
            update_post_meta($post_id, '_bundle_type', 'flexible');
        }
        if (!get_post_meta($post_id, '_bundle_status', true)) {
            update_post_meta($post_id, '_bundle_status', 'active');
        }
        if (!get_post_meta($post_id, '_bundle_rules', true)) {
            update_post_meta($post_id, '_bundle_rules', array(
                'min_items' => 1,
                'max_items' => 10,
                'allow_duplicates' => 'no',
                'required_categories' => array(),
                'excluded_products' => array(),
            ));
        }
        if (!get_post_meta($post_id, '_bundle_pricing', true)) {
            update_post_meta($post_id, '_bundle_pricing', array(
                'type' => 'percentage',
                'discount_value' => 0,
                'tiered_rules' => array(),
            ));
        }
        if (!get_post_meta($post_id, '_bundle_products', true)) {
            update_post_meta($post_id, '_bundle_products', array());
        }
        if (!get_post_meta($post_id, '_bundle_display_settings', true)) {
            update_post_meta($post_id, '_bundle_display_settings', array(
                'show_individual_prices' => 'yes',
                'show_savings' => 'yes',
                'layout' => 'grid',
            ));
        }
    }
}
