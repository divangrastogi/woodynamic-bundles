<?php
/**
 * Bundle Session Class
 *
 * Handles saving and retrieving bundle configurations for users
 *
 * @package WooDynamic\Bundles
 */

namespace WooDynamic\Bundles;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bundle_Session class
 */
class Bundle_Session {

    /**
     * Table name for saved bundles
     */
    const TABLE_NAME = 'woodynamic_saved_bundles';

    /**
     * Save bundle configuration for user
     *
     * @param int $user_id
     * @param int $bundle_template_id
     * @param array $products [product_id => quantity]
     * @param string $name
     * @return int|WP_Error Bundle ID or error
     */
    public static function woodynamic_save_bundle($user_id, $bundle_template_id, $products, $name = '') {
        global $wpdb;

        // Validate inputs
        if (!$user_id || !$bundle_template_id) {
            return new WP_Error('invalid_input', __('Invalid user or bundle template ID.', WOODYNAMIC_BUNDLES_TEXT_DOMAIN));
        }

        if (empty($products) || !is_array($products)) {
            return new WP_Error('invalid_products', __('No products selected.', WOODYNAMIC_BUNDLES_TEXT_DOMAIN));
        }

        // Check limits
        $max_saved = get_option('woodynamic_max_saved_bundles', 5);
        $existing_count = self::woodynamic_get_saved_bundles_count($user_id);

        if ($existing_count >= $max_saved) {
            return new WP_Error('limit_exceeded', sprintf(__('Maximum of %d saved bundles allowed.', WOODYNAMIC_BUNDLES_TEXT_DOMAIN), $max_saved));
        }

        // Prepare data
        $bundle_data = array(
            'bundle_template_id' => $bundle_template_id,
            'products' => $products,
            'name' => sanitize_text_field($name),
            'created_at' => current_time('mysql'),
        );

        $table_name = $wpdb->prefix . self::TABLE_NAME;

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return new WP_Error('table_missing', __('Database table not found.', WOODYNAMIC_BUNDLES_TEXT_DOMAIN));
        }

        // Insert or update
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'bundle_template_id' => $bundle_template_id,
                'bundle_name' => $bundle_data['name'],
                'bundle_data' => wp_json_encode($bundle_data),
                'created_at' => $bundle_data['created_at'],
                'updated_at' => current_time('mysql'),
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to save bundle.', WOODYNAMIC_BUNDLES_TEXT_DOMAIN));
        }

        $bundle_id = $wpdb->insert_id;

        do_action('woodynamic_bundle_saved', $bundle_id, $user_id, $bundle_data);

        return $bundle_id;
    }

    /**
     * Get saved bundles for user
     *
     * @param int $user_id
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public static function woodynamic_get_saved_bundles($user_id, $limit = 10, $offset = 0) {
        global $wpdb;

        if (!$user_id) {
            return array();
        }

        $table_name = $wpdb->prefix . self::TABLE_NAME;

        $query = $wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d ORDER BY updated_at DESC LIMIT %d OFFSET %d",
            $user_id, $limit, $offset
        );

        $results = $wpdb->get_results($query);

        $bundles = array();
        foreach ($results as $result) {
            $bundle_data = json_decode($result->bundle_data, true);
            if ($bundle_data) {
                $bundles[] = array(
                    'id' => $result->id,
                    'bundle_template_id' => $result->bundle_template_id,
                    'name' => $result->bundle_name,
                    'data' => $bundle_data,
                    'created_at' => $result->created_at,
                    'updated_at' => $result->updated_at,
                );
            }
        }

        return $bundles;
    }

    /**
     * Delete saved bundle
     *
     * @param int $bundle_id
     * @param int $user_id
     * @return bool|WP_Error
     */
    public static function woodynamic_delete_saved_bundle($bundle_id, $user_id) {
        global $wpdb;

        if (!$bundle_id || !$user_id) {
            return new WP_Error('invalid_input', __('Invalid bundle or user ID.', WOODYNAMIC_BUNDLES_TEXT_DOMAIN));
        }

        $table_name = $wpdb->prefix . self::TABLE_NAME;

        $result = $wpdb->delete(
            $table_name,
            array(
                'id' => $bundle_id,
                'user_id' => $user_id,
            ),
            array('%d', '%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to delete bundle.', WOODYNAMIC_BUNDLES_TEXT_DOMAIN));
        }

        if ($result === 0) {
            return new WP_Error('not_found', __('Bundle not found.', WOODYNAMIC_BUNDLES_TEXT_DOMAIN));
        }

        do_action('woodynamic_bundle_deleted', $bundle_id, $user_id);

        return true;
    }

    /**
     * Get count of saved bundles for user
     *
     * @param int $user_id
     * @return int
     */
    public static function woodynamic_get_saved_bundles_count($user_id) {
        global $wpdb;

        if (!$user_id) {
            return 0;
        }

        $table_name = $wpdb->prefix . self::TABLE_NAME;

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d",
            $user_id
        ));
    }

    /**
     * Get single saved bundle
     *
     * @param int $bundle_id
     * @param int $user_id
     * @return array|null
     */
    public static function woodynamic_get_saved_bundle($bundle_id, $user_id) {
        global $wpdb;

        if (!$bundle_id || !$user_id) {
            return null;
        }

        $table_name = $wpdb->prefix . self::TABLE_NAME;

        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d AND user_id = %d",
            $bundle_id, $user_id
        ));

        if (!$result) {
            return null;
        }

        $bundle_data = json_decode($result->bundle_data, true);
        if (!$bundle_data) {
            return null;
        }

        return array(
            'id' => $result->id,
            'bundle_template_id' => $result->bundle_template_id,
            'name' => $result->bundle_name,
            'data' => $bundle_data,
            'created_at' => $result->created_at,
            'updated_at' => $result->updated_at,
        );
    }

    /**
     * Save bundle to session (for guests)
     *
     * @param array $products
     * @param int $bundle_template_id
     * @param string $name
     * @return string Session key
     */
    public static function woodynamic_save_bundle_to_session($products, $bundle_template_id, $name = '') {
        if (!WC()->session) {
            return '';
        }

        $session_key = 'woodynamic_bundle_' . uniqid();
        $bundle_data = array(
            'bundle_template_id' => $bundle_template_id,
            'products' => $products,
            'name' => sanitize_text_field($name),
            'created_at' => current_time('mysql'),
        );

        // Get existing bundles from session
        $saved_bundles = WC()->session->get('woodynamic_saved_bundles', array());

        // Limit to 5 bundles for guests
        if (count($saved_bundles) >= 5) {
            array_shift($saved_bundles); // Remove oldest
        }

        $saved_bundles[$session_key] = $bundle_data;
        WC()->session->set('woodynamic_saved_bundles', $saved_bundles);

        return $session_key;
    }

    /**
     * Get saved bundles from session
     *
     * @return array
     */
    public static function woodynamic_get_session_bundles() {
        if (!WC()->session) {
            return array();
        }

        return WC()->session->get('woodynamic_saved_bundles', array());
    }

    /**
     * Delete bundle from session
     *
     * @param string $session_key
     * @return bool
     */
    public static function woodynamic_delete_session_bundle($session_key) {
        if (!WC()->session) {
            return false;
        }

        $saved_bundles = WC()->session->get('woodynamic_saved_bundles', array());

        if (isset($saved_bundles[$session_key])) {
            unset($saved_bundles[$session_key]);
            WC()->session->set('woodynamic_saved_bundles', $saved_bundles);
            return true;
        }

        return false;
    }

    /**
     * Get bundle data by ID (handles both user and session bundles)
     *
     * @param int|string $bundle_id
     * @param int $user_id
     * @return array|null
     */
    public static function woodynamic_get_bundle_by_id($bundle_id, $user_id = 0) {
        // Check if it's a numeric ID (database bundle)
        if (is_numeric($bundle_id) && $user_id) {
            return self::woodynamic_get_saved_bundle($bundle_id, $user_id);
        }

        // Check if it's a session key
        if (is_string($bundle_id)) {
            $session_bundles = self::woodynamic_get_session_bundles();
            return isset($session_bundles[$bundle_id]) ? $session_bundles[$bundle_id] : null;
        }

        return null;
    }

    /**
     * Update bundle name
     *
     * @param int $bundle_id
     * @param int $user_id
     * @param string $name
     * @return bool|WP_Error
     */
    public static function woodynamic_update_bundle_name($bundle_id, $user_id, $name) {
        global $wpdb;

        if (!$bundle_id || !$user_id) {
            return new WP_Error('invalid_input', __('Invalid bundle or user ID.', WOODYNAMIC_BUNDLES_TEXT_DOMAIN));
        }

        $table_name = $wpdb->prefix . self::TABLE_NAME;

        $result = $wpdb->update(
            $table_name,
            array(
                'bundle_name' => sanitize_text_field($name),
                'updated_at' => current_time('mysql'),
            ),
            array(
                'id' => $bundle_id,
                'user_id' => $user_id,
            ),
            array('%s', '%s'),
            array('%d', '%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to update bundle.', WOODYNAMIC_BUNDLES_TEXT_DOMAIN));
        }

        return true;
    }

    /**
     * Reorder bundle products
     *
     * @param int $bundle_id
     * @param int $user_id
     * @param array $new_order [product_id => quantity]
     * @return bool|WP_Error
     */
    public static function woodynamic_reorder_bundle($bundle_id, $user_id, $new_order) {
        $bundle = self::woodynamic_get_saved_bundle($bundle_id, $user_id);

        if (!$bundle) {
            return new WP_Error('not_found', __('Bundle not found.', WOODYNAMIC_BUNDLES_TEXT_DOMAIN));
        }

        // Update the products order
        $bundle['data']['products'] = $new_order;
        $bundle['data']['updated_at'] = current_time('mysql');

        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;

        $result = $wpdb->update(
            $table_name,
            array(
                'bundle_data' => wp_json_encode($bundle['data']),
                'updated_at' => current_time('mysql'),
            ),
            array(
                'id' => $bundle_id,
                'user_id' => $user_id,
            ),
            array('%s', '%s'),
            array('%d', '%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to reorder bundle.', WOODYNAMIC_BUNDLES_TEXT_DOMAIN));
        }

        return true;
    }
}
