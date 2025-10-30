<?php
/**
 * Database handler for WCFM Product Affiliate
 *
 * @package WCFM_Product_Affiliate
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCFM_Affiliate_DB {
    
    /**
     * Table name for affiliate relationships
     */
    const TABLE_AFFILIATES = 'wcfm_product_affiliates';
    
    /**
     * Table name for affiliate sales tracking
     */
    const TABLE_SALES = 'wcfm_affiliate_sales';
    
    /**
     * Constructor
     */
    public function __construct() {
        // Constructor if needed
    }
    
    /**
     * Create database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_prefix = $wpdb->prefix;
        
        // Table for affiliate relationships
        $sql_affiliates = "CREATE TABLE IF NOT EXISTS `{$table_prefix}" . self::TABLE_AFFILIATES . "` (
            `id` bigint(20) NOT NULL AUTO_INCREMENT,
            `vendor_id` bigint(20) NOT NULL,
            `product_id` bigint(20) NOT NULL,
            `product_owner_id` bigint(20) NOT NULL,
            `commission_rate` decimal(5,2) DEFAULT 20.00,
            `commission_type` varchar(20) DEFAULT 'percentage',
            `status` varchar(20) DEFAULT 'active',
            `is_active` tinyint(1) NOT NULL DEFAULT 1,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `vendor_product` (`vendor_id`, `product_id`),
            KEY `vendor_id` (`vendor_id`),
            KEY `product_id` (`product_id`),
            KEY `product_owner_id` (`product_owner_id`),
            KEY `status` (`status`),
            KEY `is_active` (`is_active`)
        ) $charset_collate;";
        
        // Table for tracking affiliate sales
        $sql_sales = "CREATE TABLE IF NOT EXISTS `{$table_prefix}" . self::TABLE_SALES . "` (
            `id` bigint(20) NOT NULL AUTO_INCREMENT,
            `order_id` bigint(20) NOT NULL,
            `order_item_id` bigint(20) NOT NULL,
            `product_id` bigint(20) NOT NULL,
            `vendor_id` bigint(20) NOT NULL,
            `product_owner_id` bigint(20) NOT NULL,
            `affiliate_vendor_id` bigint(20) NOT NULL,
            `product_price` decimal(10,2) DEFAULT 0.00,
            `product_quantity` int(11) DEFAULT 1,
            `product_total` decimal(10,2) DEFAULT 0.00,
            `owner_commission` decimal(10,2) DEFAULT 0.00,
            `affiliate_commission` decimal(10,2) DEFAULT 0.00,
            `commission_rate` decimal(5,2) DEFAULT 20.00,
            `order_status` varchar(50) DEFAULT 'pending',
            `commission_status` varchar(50) DEFAULT 'pending',
            `store_origin` varchar(255) DEFAULT '',
            `tracking_data` text,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `order_id` (`order_id`),
            KEY `order_item_id` (`order_item_id`),
            KEY `product_id` (`product_id`),
            KEY `affiliate_vendor_id` (`affiliate_vendor_id`),
            KEY `product_owner_id` (`product_owner_id`),
            KEY `order_status` (`order_status`),
            KEY `commission_status` (`commission_status`)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_affiliates);
        dbDelta($sql_sales);
    }
    
    /**
     * Add affiliate relationship
     */
    public function add_affiliate($vendor_id, $product_id, $commission_rate = null) {
        global $wpdb;
        
        // Get product owner
        $product_owner_id = get_post_field('post_author', $product_id);
        
        if (!$product_owner_id || $product_owner_id == $vendor_id) {
            return false;
        }
        
        // Get default commission rate if not provided
        if ($commission_rate === null) {
            $commission_rate = WCFM_Affiliate()->get_option('default_commission_rate', 1);
        }
        
        // Insert or update
        $table = $wpdb->prefix . self::TABLE_AFFILIATES;
        
        $data = array(
            'vendor_id' => $vendor_id,
            'product_id' => $product_id,
            'product_owner_id' => $product_owner_id,
            'commission_rate' => $commission_rate,
            'status' => 'active'
        );
        
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE vendor_id = %d AND product_id = %d",
            $vendor_id,
            $product_id
        ));
        
        if ($existing) {
            // Update
            $wpdb->update(
                $table,
                array('status' => 'active', 'commission_rate' => $commission_rate),
                array('id' => $existing->id)
            );
            return $existing->id;
        } else {
            // Insert
            $wpdb->insert($table, $data);
            return $wpdb->insert_id;
        }
    }
    
    /**
     * Remove affiliate relationship
     */
    public function remove_affiliate($vendor_id, $product_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . self::TABLE_AFFILIATES;
        
        return $wpdb->update(
            $table,
            array('status' => 'inactive'),
            array('vendor_id' => $vendor_id, 'product_id' => $product_id)
        );
    }
    
    /**
     * Check if vendor has affiliate relationship with product
     */
    public function has_affiliate($vendor_id, $product_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . self::TABLE_AFFILIATES;
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE vendor_id = %d AND product_id = %d AND status = 'active'",
            $vendor_id,
            $product_id
        ));
        
        return $result > 0;
    }
    
    /**
     * Get affiliate relationship
     */
    public function get_affiliate($vendor_id, $product_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . self::TABLE_AFFILIATES;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE vendor_id = %d AND product_id = %d AND status = 'active'",
            $vendor_id,
            $product_id
        ));
    }
    
    /**
     * Get all affiliate products for vendor
     */
    public function get_vendor_affiliates($vendor_id, $status = 'active') {
        global $wpdb;
        
        $table = $wpdb->prefix . self::TABLE_AFFILIATES;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE vendor_id = %d AND status = %s ORDER BY created_at DESC",
            $vendor_id,
            $status
        ));
    }
    
    /**
     * Get all affiliates for a product
     */
    public function get_product_affiliates($product_id, $status = 'active') {
        global $wpdb;
        
        $table = $wpdb->prefix . self::TABLE_AFFILIATES;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE product_id = %d AND status = %s ORDER BY created_at DESC",
            $product_id,
            $status
        ));
    }
    
    /**
     * Record affiliate sale
     */
    public function record_sale($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . self::TABLE_SALES;
        
        $defaults = array(
            'order_status' => 'pending',
            'commission_status' => 'pending',
            'store_origin' => '',
            'tracking_data' => ''
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Serialize tracking data if array
        if (is_array($data['tracking_data'])) {
            $data['tracking_data'] = maybe_serialize($data['tracking_data']);
        }
        
        $wpdb->insert($table, $data);
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get affiliate sales for vendor
     */
    public function get_vendor_sales($vendor_id, $args = array()) {
        global $wpdb;
        
        $table = $wpdb->prefix . self::TABLE_SALES;
        
        $defaults = array(
            'limit' => 20,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $sql = $wpdb->prepare(
            "SELECT * FROM {$table} WHERE affiliate_vendor_id = %d ORDER BY {$args['orderby']} {$args['order']} LIMIT %d OFFSET %d",
            $vendor_id,
            $args['limit'],
            $args['offset']
        );
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Get statistics for vendor
     */
    public function get_vendor_stats($vendor_id, $date_from = null, $date_to = null) {
        global $wpdb;
        
        $table = $wpdb->prefix . self::TABLE_SALES;
        
        $where = $wpdb->prepare("WHERE affiliate_vendor_id = %d", $vendor_id);
        
        if ($date_from) {
            $where .= $wpdb->prepare(" AND created_at >= %s", $date_from);
        }
        
        if ($date_to) {
            $where .= $wpdb->prepare(" AND created_at <= %s", $date_to);
        }
        
        $sql = "SELECT 
                COUNT(*) as total_sales,
                SUM(affiliate_commission) as total_commission,
                SUM(product_total) as total_product_value
                FROM {$table} {$where}";
        
        return $wpdb->get_row($sql);
    }
    
    /**
     * Update commission status
     */
    public function update_commission_status($sale_id, $status) {
        global $wpdb;
        
        $table = $wpdb->prefix . self::TABLE_SALES;
        
        return $wpdb->update(
            $table,
            array('commission_status' => $status),
            array('id' => $sale_id)
        );
    }
}

