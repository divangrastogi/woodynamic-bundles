<?php
/**
 * REST Controller Class
 *
 * Handles REST API endpoints for bundles
 *
 * @package WooDynamic\Bundles
 */

namespace WooDynamic\Bundles;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * REST_Controller class
 */
class REST_Controller {

    /**
     * Plugin name
     *
     * @var string
     */
    private $plugin_name;

    /**
     * Plugin version
     *
     * @var string
     */
    private $version;

    /**
     * Namespace
     */
    const NAMESPACE = 'woodynamic/v1';

    /**
     * Initialize the class
     *
     * @param string $plugin_name
     * @param string $version
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register REST API routes
     */
    public function woodynamic_register_routes() {
        register_rest_route(self::NAMESPACE, '/bundles', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'woodynamic_get_bundles'),
                'permission_callback' => '__return_true',
                'args' => array(
                    'category' => array(
                        'required' => false,
                        'type' => 'integer',
                    ),
                    'search' => array(
                        'required' => false,
                        'type' => 'string',
                    ),
                    'per_page' => array(
                        'required' => false,
                        'type' => 'integer',
                        'default' => 10,
                        'minimum' => 1,
                        'maximum' => 100,
                    ),
                    'page' => array(
                        'required' => false,
                        'type' => 'integer',
                        'default' => 1,
                        'minimum' => 1,
                    ),
                ),
            ),
        ));

        register_rest_route(self::NAMESPACE, '/bundles/(?P<id>\d+)', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'woodynamic_get_bundle'),
                'permission_callback' => '__return_true',
                'args' => array(
                    'id' => array(
                        'required' => true,
                        'type' => 'integer',
                        'minimum' => 1,
                    ),
                ),
            ),
        ));

        register_rest_route(self::NAMESPACE, '/bundles/(?P<id>\d+)/calculate', array(
            array(
                'methods' => 'POST',
                'callback' => array($this, 'woodynamic_calculate_bundle'),
                'permission_callback' => '__return_true',
                'args' => array(
                    'id' => array(
                        'required' => true,
                        'type' => 'integer',
                        'minimum' => 1,
                    ),
                    'products' => array(
                        'required' => true,
                        'type' => 'array',
                        'items' => array(
                            'type' => 'object',
                            'properties' => array(
                                'id' => array('type' => 'integer'),
                                'qty' => array('type' => 'integer'),
                            ),
                        ),
                    ),
                ),
            ),
        ));

        register_rest_route(self::NAMESPACE, '/bundles/(?P<id>\d+)/validate', array(
            array(
                'methods' => 'POST',
                'callback' => array($this, 'woodynamic_validate_bundle'),
                'permission_callback' => '__return_true',
                'args' => array(
                    'id' => array(
                        'required' => true,
                        'type' => 'integer',
                        'minimum' => 1,
                    ),
                    'products' => array(
                        'required' => true,
                        'type' => 'array',
                    ),
                ),
            ),
        ));

        register_rest_route(self::NAMESPACE, '/bundles/(?P<id>\d+)/add-to-cart', array(
            array(
                'methods' => 'POST',
                'callback' => array($this, 'woodynamic_add_bundle_to_cart'),
                'permission_callback' => '__return_true',
                'args' => array(
                    'id' => array(
                        'required' => true,
                        'type' => 'integer',
                        'minimum' => 1,
                    ),
                    'products' => array(
                        'required' => true,
                        'type' => 'array',
                    ),
                ),
            ),
        ));

        // Saved bundles endpoints (require authentication)
        register_rest_route(self::NAMESPACE, '/saved-bundles', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'woodynamic_get_saved_bundles'),
                'permission_callback' => array($this, 'woodynamic_check_authentication'),
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'woodynamic_save_bundle'),
                'permission_callback' => array($this, 'woodynamic_check_authentication'),
                'args' => array(
                    'bundle_id' => array(
                        'required' => true,
                        'type' => 'integer',
                    ),
                    'products' => array(
                        'required' => true,
                        'type' => 'array',
                    ),
                    'name' => array(
                        'required' => false,
                        'type' => 'string',
                    ),
                ),
            ),
        ));

        register_rest_route(self::NAMESPACE, '/saved-bundles/(?P<id>\d+)', array(
            array(
                'methods' => 'DELETE',
                'callback' => array($this, 'woodynamic_delete_saved_bundle'),
                'permission_callback' => array($this, 'woodynamic_check_authentication'),
                'args' => array(
                    'id' => array(
                        'required' => true,
                        'type' => 'integer',
                    ),
                ),
            ),
        ));
    }

    /**
     * Get bundles
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function woodynamic_get_bundles($request) {
        $args = array(
            'post_type' => 'wc_bundle_template',
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_bundle_status',
                    'value' => 'active',
                    'compare' => '=',
                ),
            ),
            'posts_per_page' => $request->get_param('per_page') ?: 10,
            'paged' => $request->get_param('page') ?: 1,
        );

        // Filter by category
        if ($request->get_param('category')) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'bundle_category',
                    'field' => 'term_id',
                    'terms' => $request->get_param('category'),
                ),
            );
        }

        // Search
        if ($request->get_param('search')) {
            $args['s'] = $request->get_param('search');
        }

        $query = new WP_Query($args);
        $bundles = array();

        foreach ($query->posts as $post) {
            $bundles[] = $this->woodynamic_format_bundle_data($post);
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $bundles,
            'pagination' => array(
                'total' => $query->found_posts,
                'per_page' => $args['posts_per_page'],
                'current_page' => $args['paged'],
                'total_pages' => $query->max_num_pages,
            ),
        ), 200);
    }

    /**
     * Get single bundle
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function woodynamic_get_bundle($request) {
        $bundle_id = $request->get_param('id');
        $bundle_data = CPT_Bundle_Template::woodynamic_get_bundle_data($bundle_id);

        if (!$bundle_data || $bundle_data['status'] !== 'active') {
            return new WP_Error('bundle_not_found', __('Bundle not found', WOODYNAMIC_BUNDLES_TEXT_DOMAIN), array('status' => 404));
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $bundle_data,
        ), 200);
    }

    /**
     * Calculate bundle price
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function woodynamic_calculate_bundle($request) {
        $bundle_id = $request->get_param('id');
        $products = $request->get_param('products');

        // Convert products array
        $products_array = array();
        foreach ($products as $product) {
            $products_array[$product['id']] = $product['qty'];
        }

        $bundle_config = CPT_Bundle_Template::woodynamic_get_bundle_data($bundle_id);

        if (!$bundle_config) {
            return new WP_Error('bundle_not_found', __('Bundle not found', WOODYNAMIC_BUNDLES_TEXT_DOMAIN), array('status' => 404));
        }

        $calculation = Bundle_Calculator::woodynamic_calculate_bundle_price($bundle_config, $products_array);

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $calculation,
        ), 200);
    }

    /**
     * Validate bundle
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function woodynamic_validate_bundle($request) {
        $bundle_id = $request->get_param('id');
        $products = $request->get_param('products');

        // Convert products array
        $products_array = array();
        foreach ($products as $product) {
            $products_array[$product['id']] = $product['qty'];
        }

        $bundle_config = CPT_Bundle_Template::woodynamic_get_bundle_data($bundle_id);

        if (!$bundle_config) {
            return new WP_Error('bundle_not_found', __('Bundle not found', WOODYNAMIC_BUNDLES_TEXT_DOMAIN), array('status' => 404));
        }

        $validation = Bundle_Calculator::woodynamic_validate_bundle($bundle_config, $products_array);

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $validation,
        ), 200);
    }

    /**
     * Add bundle to cart
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function woodynamic_add_bundle_to_cart($request) {
        $bundle_id = $request->get_param('id');
        $products = $request->get_param('products');

        // Convert products array
        $products_array = array();
        foreach ($products as $product) {
            $products_array[$product['id']] = $product['qty'];
        }

        $cart_integration = new Cart_Integration($this->plugin_name, $this->version);
        $result = $cart_integration->woodynamic_add_bundle_to_cart($bundle_id, $products_array);

        if (is_wp_error($result)) {
            return new WP_Error('cart_error', $result->get_error_message(), array('status' => 400));
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'cart_count' => WC()->cart->get_cart_contents_count(),
                'cart_total' => WC()->cart->get_total(),
                'cart_url' => wc_get_cart_url(),
            ),
            'message' => __('Bundle added to cart successfully', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
        ), 200);
    }

    /**
     * Get saved bundles
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function woodynamic_get_saved_bundles($request) {
        $user_id = get_current_user_id();
        $bundles = Bundle_Session::woodynamic_get_saved_bundles($user_id);

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $bundles,
        ), 200);
    }

    /**
     * Save bundle
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function woodynamic_save_bundle($request) {
        $user_id = get_current_user_id();
        $bundle_id = $request->get_param('bundle_id');
        $products = $request->get_param('products');
        $name = $request->get_param('name');

        $result = Bundle_Session::woodynamic_save_bundle($user_id, $bundle_id, $products, $name);

        if (is_wp_error($result)) {
            return new WP_Error('save_error', $result->get_error_message(), array('status' => 400));
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data' => array('bundle_id' => $result),
            'message' => __('Bundle saved successfully', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
        ), 201);
    }

    /**
     * Delete saved bundle
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function woodynamic_delete_saved_bundle($request) {
        $user_id = get_current_user_id();
        $bundle_id = $request->get_param('id');

        $result = Bundle_Session::woodynamic_delete_saved_bundle($bundle_id, $user_id);

        if (is_wp_error($result)) {
            return new WP_Error('delete_error', $result->get_error_message(), array('status' => 400));
        }

        return new WP_REST_Response(array(
            'success' => true,
            'message' => __('Bundle deleted successfully', WOODYNAMIC_BUNDLES_TEXT_DOMAIN),
        ), 200);
    }

    /**
     * Check if user is authenticated
     *
     * @param WP_REST_Request $request
     * @return bool
     */
    public function woodynamic_check_authentication($request) {
        return is_user_logged_in();
    }

    /**
     * Format bundle data for API response
     *
     * @param WP_Post $post
     * @return array
     */
    private function woodynamic_format_bundle_data($post) {
        $bundle_data = CPT_Bundle_Template::woodynamic_get_bundle_data($post->ID);

        return array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'description' => $post->post_content,
            'type' => $bundle_data['type'],
            'rules' => $bundle_data['rules'],
            'pricing' => $bundle_data['pricing'],
            'thumbnail' => $bundle_data['thumbnail'],
            'permalink' => get_permalink($post->ID),
        );
    }
}
