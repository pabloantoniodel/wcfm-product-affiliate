<?php
/**
 * Tracking handler for WCFM Product Affiliate
 * Tracks the origin store when products are added to cart
 *
 * @package WCFM_Product_Affiliate
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCFM_Affiliate_Tracking {
    
    /**
     * Session key for store origin
     */
    const SESSION_KEY = 'wcfm_affiliate_store_origin';
    
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
        // Track store visits
        add_action('template_redirect', array($this, 'track_store_visit'), 10);
        
        // Track add to cart
        add_action('woocommerce_add_to_cart', array($this, 'track_add_to_cart'), 10, 6);
        
        // Add tracking data to cart items
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_cart_item_data'), 10, 3);
        
        // Display tracking info in cart (for debugging)
        // add_filter('woocommerce_get_item_data', array($this, 'display_cart_item_data'), 10, 2);
        
        // Save tracking data to order
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'save_order_item_tracking'), 10, 4);
        
        // Save store origin to order
        add_action('woocommerce_checkout_order_processed', array($this, 'save_order_tracking'), 10, 3);
    }
    
    /**
     * Track store visit
     */
    public function track_store_visit() {
        // Check if we're on a store page
        if (!function_exists('wcfmmp_get_store_url')) {
            return;
        }
        
        global $WCFMmp;
        
        // Get current vendor ID from store page
        $vendor_id = 0;
        
        // Check if on store page
        if (function_exists('wcfm_is_store_page') && wcfm_is_store_page()) {
            $store_name = get_query_var($WCFMmp->wcfm_store_url);
            if ($store_name) {
                $seller_info = get_user_by('slug', $store_name);
                if ($seller_info) {
                    $vendor_id = $seller_info->ID;
                }
            }
        }
        
        // Check URL parameter
        if (!$vendor_id && isset($_GET['store_origin'])) {
            $vendor_id = intval($_GET['store_origin']);
        }
        
        if (!$vendor_id && isset($_GET['vendor'])) {
            $vendor_id = intval($_GET['vendor']);
        }
        
        // Save to session
        if ($vendor_id) {
            $this->set_store_origin($vendor_id);
        }
    }
    
    /**
     * Track add to cart
     */
    public function track_add_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
        // Get current store origin
        $store_origin = $this->get_store_origin();
        
        if ($store_origin) {
            // Save to session with cart item key
            $this->set_cart_item_store($cart_item_key, $store_origin);
        }
    }
    
    /**
     * Add tracking data to cart item
     */
    public function add_cart_item_data($cart_item_data, $product_id, $variation_id) {
        $store_origin = $this->get_store_origin();
        
        if ($store_origin) {
            $cart_item_data['wcfm_affiliate_store'] = $store_origin;
            $cart_item_data['wcfm_affiliate_time'] = time();
        }
        
        return $cart_item_data;
    }
    
    /**
     * Display tracking info in cart (for debugging)
     */
    public function display_cart_item_data($item_data, $cart_item) {
        if (isset($cart_item['wcfm_affiliate_store']) && defined('WP_DEBUG') && WP_DEBUG) {
            $vendor_id = $cart_item['wcfm_affiliate_store'];
            $vendor = get_userdata($vendor_id);
            
            $item_data[] = array(
                'name' => __('Store Origin', 'wcfm-product-affiliate'),
                'value' => $vendor ? $vendor->display_name : $vendor_id
            );
        }
        
        return $item_data;
    }
    
    /**
     * Save tracking data to order item
     */
    public function save_order_item_tracking($item, $cart_item_key, $values, $order) {
        if (isset($values['wcfm_affiliate_store'])) {
            $item->add_meta_data('_wcfm_affiliate_store', $values['wcfm_affiliate_store'], true);
            $item->add_meta_data('_wcfm_affiliate_time', isset($values['wcfm_affiliate_time']) ? $values['wcfm_affiliate_time'] : time(), true);
        }
    }
    
    /**
     * Save store origin to order
     */
    public function save_order_tracking($order_id, $posted_data, $order) {
        $store_origin = $this->get_store_origin();
        
        if ($store_origin) {
            $order->update_meta_data('_wcfm_affiliate_store_origin', $store_origin);
            $order->save();
        }
        
        // Clear session
        $this->clear_store_origin();
    }
    
    /**
     * Get store origin from session
     */
    public function get_store_origin() {
        if (!WC()->session) {
            return null;
        }
        
        return WC()->session->get(self::SESSION_KEY);
    }
    
    /**
     * Set store origin in session
     */
    public function set_store_origin($vendor_id) {
        if (!WC()->session) {
            return;
        }
        
        WC()->session->set(self::SESSION_KEY, $vendor_id);
    }
    
    /**
     * Clear store origin from session
     */
    public function clear_store_origin() {
        if (!WC()->session) {
            return;
        }
        
        WC()->session->__unset(self::SESSION_KEY);
    }
    
    /**
     * Get cart item store
     */
    public function get_cart_item_store($cart_item_key) {
        if (!WC()->session) {
            return null;
        }
        
        $cart_stores = WC()->session->get('wcfm_affiliate_cart_stores', array());
        
        return isset($cart_stores[$cart_item_key]) ? $cart_stores[$cart_item_key] : null;
    }
    
    /**
     * Set cart item store
     */
    public function set_cart_item_store($cart_item_key, $vendor_id) {
        if (!WC()->session) {
            return;
        }
        
        $cart_stores = WC()->session->get('wcfm_affiliate_cart_stores', array());
        $cart_stores[$cart_item_key] = $vendor_id;
        WC()->session->set('wcfm_affiliate_cart_stores', $cart_stores);
    }
    
    /**
     * Get order item affiliate vendor
     */
    public function get_order_item_affiliate($order_item) {
        if (is_numeric($order_item)) {
            $order_item = new WC_Order_Item_Product($order_item);
        }
        
        return $order_item->get_meta('_wcfm_affiliate_store', true);
    }
    
    /**
     * Get order store origin
     */
    public function get_order_store_origin($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return null;
        }
        
        return $order->get_meta('_wcfm_affiliate_store_origin', true);
    }
}

