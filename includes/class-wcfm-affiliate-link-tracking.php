<?php
/**
 * Link Tracking handler for WCFM Product Affiliate
 * Tracks when vendors share affiliate product links and records visits
 *
 * @package WCFM_Product_Affiliate
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCFM_Affiliate_Link_Tracking {
    
    /**
     * Table name for link clicks tracking
     */
    const TABLE_LINK_CLICKS = 'wcfm_affiliate_link_clicks';
    
    /**
     * URL parameter for vendor reference
     */
    const URL_PARAM = 'ref_vendor';
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Track link visits
        add_action('template_redirect', array($this, 'track_link_visit'), 5);
        
        // Modify product links for affiliate vendors
        add_filter('post_link', array($this, 'add_vendor_ref_to_link'), 10, 2);
        add_filter('post_type_link', array($this, 'add_vendor_ref_to_link'), 10, 2);
        
        // Add vendor reference to product permalinks in loops
        add_filter('woocommerce_loop_product_link', array($this, 'add_vendor_ref_to_product_link'), 10, 2);
        
        // AJAX endpoint to get shareable link
        add_action('wp_ajax_get_affiliate_share_link', array($this, 'ajax_get_share_link'));
    }
    
    /**
     * Track link visit when someone clicks on a shared affiliate link
     */
    public function track_link_visit() {
        // Check if we have a vendor reference in URL
        $ref_vendor_id = 0;
        
        // Try ref_vendor parameter (numeric ID)
        if (isset($_GET[self::URL_PARAM])) {
            $ref_vendor_id = intval($_GET[self::URL_PARAM]);
        }
        
        // Try ref parameter (username/slug)
        if (!$ref_vendor_id && isset($_GET['ref'])) {
            $ref_username = sanitize_text_field($_GET['ref']);
            $user = get_user_by('login', $ref_username);
            if (!$user) {
                $user = get_user_by('slug', $ref_username);
            }
            if ($user) {
                $ref_vendor_id = $user->ID;
            }
        }
        
        if (!$ref_vendor_id) {
            return;
        }
        
        // Check if we're on a product page
        if (!is_product()) {
            return;
        }
        
        global $post;
        $product_id = $post->ID;
        
        // Get product owner
        $product_owner_id = get_post_field('post_author', $product_id);
        
        // Don't track if the ref_vendor is the product owner (not an affiliate)
        if ($ref_vendor_id == $product_owner_id) {
            return;
        }
        
        // Check if ref_vendor actually has this product as affiliate
        global $wpdb;
        $affiliates_table = $wpdb->prefix . WCFM_Affiliate_DB::TABLE_AFFILIATES;
        
        $is_affiliate = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$affiliates_table} 
            WHERE vendor_id = %d AND product_id = %d AND status = 'active'",
            $ref_vendor_id,
            $product_id
        ));
        
        // Allow tracking even if not an official affiliate (for general link sharing)
        // This helps vendors see all their shared links, not just affiliate products
        // if (!$is_affiliate) {
        //     return;
        // }
        
        // Record the click
        $this->record_link_click(array(
            'product_id' => $product_id,
            'product_owner_id' => $product_owner_id,
            'affiliate_vendor_id' => $ref_vendor_id,
            'visitor_ip' => $this->get_visitor_ip(),
            'visitor_user_agent' => $this->get_user_agent(),
            'referrer_url' => $this->get_referrer(),
            'visitor_user_id' => get_current_user_id() // 0 if not logged in
        ));
        
        // Set this vendor as the store origin for purchase tracking
        if (class_exists('WCFM_Affiliate_Tracking')) {
            $tracking = new WCFM_Affiliate_Tracking();
            $tracking->set_store_origin($ref_vendor_id);
        }
    }
    
    /**
     * Add vendor reference to product links automatically
     */
    public function add_vendor_ref_to_link($permalink, $post) {
        // Only for products
        if (!is_object($post) || $post->post_type !== 'product') {
            return $permalink;
        }
        
        // Only add reference if user is logged in and is a vendor
        if (!is_user_logged_in() || !current_user_can('wcfm_vendor')) {
            return $permalink;
        }
        
        $current_vendor_id = get_current_user_id();
        $product_owner_id = $post->post_author;
        
        // Don't add ref if user is the product owner
        if ($current_vendor_id == $product_owner_id) {
            return $permalink;
        }
        
        // Check if current user has this product as affiliate
        $db = new WCFM_Affiliate_DB();
        if (!$db->has_affiliate($current_vendor_id, $post->ID)) {
            return $permalink;
        }
        
        // Add vendor reference
        return add_query_arg(self::URL_PARAM, $current_vendor_id, $permalink);
    }
    
    /**
     * Add vendor reference to product links in WooCommerce loops
     */
    public function add_vendor_ref_to_product_link($link, $product) {
        // Only if user is logged in and is a vendor
        if (!is_user_logged_in() || !current_user_can('wcfm_vendor')) {
            return $link;
        }
        
        $current_vendor_id = get_current_user_id();
        $product_id = $product->get_id();
        $product_owner_id = get_post_field('post_author', $product_id);
        
        // Don't add ref if user is the product owner
        if ($current_vendor_id == $product_owner_id) {
            return $link;
        }
        
        // Check if current user has this product as affiliate
        $db = new WCFM_Affiliate_DB();
        if (!$db->has_affiliate($current_vendor_id, $product_id)) {
            return $link;
        }
        
        // Add vendor reference
        return add_query_arg(self::URL_PARAM, $current_vendor_id, $link);
    }
    
    /**
     * AJAX: Get shareable link for a product
     */
    public function ajax_get_share_link() {
        check_ajax_referer('wcfm_affiliate_nonce', 'nonce');
        
        if (!is_user_logged_in() || !current_user_can('wcfm_vendor')) {
            wp_send_json_error(__('No tienes permisos', 'wcfm-product-affiliate'));
        }
        
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        
        if (!$product_id) {
            wp_send_json_error(__('ID de producto inválido', 'wcfm-product-affiliate'));
        }
        
        $vendor_id = get_current_user_id();
        $product_owner_id = get_post_field('post_author', $product_id);
        
        // Check if it's an affiliate product
        $db = new WCFM_Affiliate_DB();
        if (!$db->has_affiliate($vendor_id, $product_id)) {
            wp_send_json_error(__('Este no es un producto afiliado', 'wcfm-product-affiliate'));
        }
        
        // Generate shareable link
        $permalink = get_permalink($product_id);
        $share_link = add_query_arg(self::URL_PARAM, $vendor_id, $permalink);
        
        wp_send_json_success(array(
            'link' => $share_link,
            'product_title' => get_the_title($product_id)
        ));
    }
    
    /**
     * Record a link click
     */
    private function record_link_click($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . self::TABLE_LINK_CLICKS;
        
        $defaults = array(
            'product_id' => 0,
            'product_owner_id' => 0,
            'affiliate_vendor_id' => 0,
            'visitor_ip' => '',
            'visitor_user_agent' => '',
            'referrer_url' => '',
            'visitor_user_id' => 0,
            'converted_to_sale' => 0
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Check if we already tracked this click (same IP + product + vendor in last 30 minutes)
        $recent_click = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} 
            WHERE product_id = %d 
            AND affiliate_vendor_id = %d 
            AND visitor_ip = %s 
            AND created_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)",
            $data['product_id'],
            $data['affiliate_vendor_id'],
            $data['visitor_ip']
        ));
        
        // Don't record duplicate clicks within 30 minutes
        if ($recent_click > 0) {
            return false;
        }
        
        $wpdb->insert($table, $data);
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get link clicks for a vendor (as affiliate)
     */
    public function get_vendor_link_clicks($vendor_id, $args = array()) {
        global $wpdb;
        
        $table = $wpdb->prefix . self::TABLE_LINK_CLICKS;
        
        $defaults = array(
            'limit' => 50,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'product_id' => null,
            'date_from' => null,
            'date_to' => null
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = $wpdb->prepare("WHERE affiliate_vendor_id = %d", $vendor_id);
        
        if ($args['product_id']) {
            $where .= $wpdb->prepare(" AND product_id = %d", $args['product_id']);
        }
        
        if ($args['date_from']) {
            $where .= $wpdb->prepare(" AND created_at >= %s", $args['date_from']);
        }
        
        if ($args['date_to']) {
            $where .= $wpdb->prepare(" AND created_at <= %s", $args['date_to']);
        }
        
        $sql = "SELECT * FROM {$table} 
                {$where} 
                ORDER BY {$args['orderby']} {$args['order']} 
                LIMIT {$args['limit']} OFFSET {$args['offset']}";
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Get link clicks for a product (as owner)
     */
    public function get_product_link_clicks($product_id, $args = array()) {
        global $wpdb;
        
        $table = $wpdb->prefix . self::TABLE_LINK_CLICKS;
        
        $defaults = array(
            'limit' => 50,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'date_from' => null,
            'date_to' => null
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = $wpdb->prepare("WHERE product_id = %d", $product_id);
        
        if ($args['date_from']) {
            $where .= $wpdb->prepare(" AND created_at >= %s", $args['date_from']);
        }
        
        if ($args['date_to']) {
            $where .= $wpdb->prepare(" AND created_at <= %s", $args['date_to']);
        }
        
        $sql = "SELECT * FROM {$table} 
                {$where} 
                ORDER BY {$args['orderby']} {$args['order']} 
                LIMIT {$args['limit']} OFFSET {$args['offset']}";
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Get link click statistics for vendor
     */
    public function get_vendor_link_stats($vendor_id, $date_from = null, $date_to = null) {
        global $wpdb;
        
        $table = $wpdb->prefix . self::TABLE_LINK_CLICKS;
        
        $where = $wpdb->prepare("WHERE affiliate_vendor_id = %d", $vendor_id);
        
        if ($date_from) {
            $where .= $wpdb->prepare(" AND created_at >= %s", $date_from);
        }
        
        if ($date_to) {
            $where .= $wpdb->prepare(" AND created_at <= %s", $date_to);
        }
        
        $sql = "SELECT 
                COUNT(*) as total_clicks,
                COUNT(DISTINCT product_id) as unique_products,
                COUNT(DISTINCT visitor_ip) as unique_visitors,
                SUM(converted_to_sale) as total_conversions
                FROM {$table} {$where}";
        
        return $wpdb->get_row($sql);
    }
    
    /**
     * Get link click statistics for product owner
     */
    public function get_product_owner_link_stats($owner_id, $date_from = null, $date_to = null) {
        global $wpdb;
        
        $table = $wpdb->prefix . self::TABLE_LINK_CLICKS;
        
        $where = $wpdb->prepare("WHERE product_owner_id = %d", $owner_id);
        
        if ($date_from) {
            $where .= $wpdb->prepare(" AND created_at >= %s", $date_from);
        }
        
        if ($date_to) {
            $where .= $wpdb->prepare(" AND created_at <= %s", $date_to);
        }
        
        $sql = "SELECT 
                COUNT(*) as total_clicks,
                COUNT(DISTINCT product_id) as unique_products,
                COUNT(DISTINCT affiliate_vendor_id) as unique_affiliates,
                COUNT(DISTINCT visitor_ip) as unique_visitors,
                SUM(converted_to_sale) as total_conversions
                FROM {$table} {$where}";
        
        return $wpdb->get_row($sql);
    }
    
    /**
     * Get link clicks for products owned by vendor (from affiliates)
     */
    public function get_product_owner_link_clicks($owner_id, $args = array()) {
        global $wpdb;
        
        $table = $wpdb->prefix . self::TABLE_LINK_CLICKS;
        
        $defaults = array(
            'limit' => 50,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'date_from' => null,
            'date_to' => null
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = $wpdb->prepare("WHERE product_owner_id = %d", $owner_id);
        
        if ($args['date_from']) {
            $where .= $wpdb->prepare(" AND created_at >= %s", $args['date_from']);
        }
        
        if ($args['date_to']) {
            $where .= $wpdb->prepare(" AND created_at <= %s", $args['date_to']);
        }
        
        $sql = "SELECT * FROM {$table} 
                {$where} 
                ORDER BY {$args['orderby']} {$args['order']} 
                LIMIT {$args['limit']} OFFSET {$args['offset']}";
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Mark a click as converted to sale
     */
    public function mark_click_converted($product_id, $affiliate_vendor_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . self::TABLE_LINK_CLICKS;
        
        // Mark the most recent click as converted
        return $wpdb->query($wpdb->prepare(
            "UPDATE {$table} 
            SET converted_to_sale = 1 
            WHERE product_id = %d 
            AND affiliate_vendor_id = %d 
            AND converted_to_sale = 0
            ORDER BY created_at DESC 
            LIMIT 1",
            $product_id,
            $affiliate_vendor_id
        ));
    }
    
    /**
     * Get visitor IP
     */
    private function get_visitor_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        // Anonymize IP for privacy (GDPR compliance)
        return $this->anonymize_ip($ip);
    }
    
    /**
     * Anonymize IP address for privacy
     */
    private function anonymize_ip($ip) {
        // For IPv4, mask last octet
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return preg_replace('/\.\d+$/', '.0', $ip);
        }
        
        // For IPv6, mask last 80 bits
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return substr($ip, 0, strrpos($ip, ':')) . ':0000';
        }
        
        return $ip;
    }
    
    /**
     * Get user agent
     */
    private function get_user_agent() {
        return isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : '';
    }
    
    /**
     * Get referrer URL
     */
    private function get_referrer() {
        return isset($_SERVER['HTTP_REFERER']) ? substr($_SERVER['HTTP_REFERER'], 0, 255) : '';
    }
    
    /**
     * Create database table
     */
    public static function create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table = $wpdb->prefix . self::TABLE_LINK_CLICKS;
        
        $sql = "CREATE TABLE IF NOT EXISTS `{$table}` (
            `id` bigint(20) NOT NULL AUTO_INCREMENT,
            `product_id` bigint(20) NOT NULL,
            `product_owner_id` bigint(20) NOT NULL,
            `affiliate_vendor_id` bigint(20) NOT NULL,
            `visitor_ip` varchar(45) DEFAULT '',
            `visitor_user_agent` varchar(255) DEFAULT '',
            `referrer_url` varchar(255) DEFAULT '',
            `visitor_user_id` bigint(20) DEFAULT 0,
            `converted_to_sale` tinyint(1) DEFAULT 0,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `product_id` (`product_id`),
            KEY `product_owner_id` (`product_owner_id`),
            KEY `affiliate_vendor_id` (`affiliate_vendor_id`),
            KEY `visitor_user_id` (`visitor_user_id`),
            KEY `created_at` (`created_at`),
            KEY `converted_to_sale` (`converted_to_sale`)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

