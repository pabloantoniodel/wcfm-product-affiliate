<?php
/**
 * Commission handler for WCFM Product Affiliate
 * Handles dual commission system (product owner + affiliate)
 *
 * @package WCFM_Product_Affiliate
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCFM_Affiliate_Commission {
    
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
        // Process affiliate commissions on order
        add_action('woocommerce_checkout_order_processed', array($this, 'process_affiliate_order'), 50, 3);
        
        // Update commission status on order status change
        add_action('woocommerce_order_status_changed', array($this, 'update_commission_status'), 50, 4);
        
        // Modify WCFM commission calculation
        add_filter('wcfmmp_order_item_processed', array($this, 'modify_wcfm_commission'), 10, 9);
        
        // Add affiliate commission to withdrawal
        add_filter('wcfm_marketplace_withdrwal_order_item_amount', array($this, 'add_affiliate_to_withdrawal'), 10, 3);
    }
    
    /**
     * Process affiliate order
     */
    public function process_affiliate_order($order_id, $posted_data, $order) {
        global $WCFMmp;
        
        if (!$order) {
            $order = wc_get_order($order_id);
        }
        
        if (!$order) {
            return;
        }
        
        // Get order items
        $order_items = $order->get_items('line_item');
        
        foreach ($order_items as $item_id => $item) {
            $product_id = $item->get_product_id();
            $product = $item->get_product();
            
            if (!$product) {
                continue;
            }
            
            // Get affiliate vendor from tracking
            $affiliate_vendor_id = WCFM_Affiliate()->tracking->get_order_item_affiliate($item);
            
            if (!$affiliate_vendor_id) {
                continue;
            }
            
            // Check if there's an affiliate relationship
            $affiliate = WCFM_Affiliate()->db->get_affiliate($affiliate_vendor_id, $product_id);
            
            if (!$affiliate) {
                continue;
            }
            
            // Get product owner
            $product_owner_id = get_post_field('post_author', $product_id);
            
            // Don't process if affiliate is the owner
            if ($affiliate_vendor_id == $product_owner_id) {
                continue;
            }
            
            // Calculate commissions
            $item_total = $item->get_total();
            $item_qty = $item->get_quantity();
            
            $affiliate_rate = floatval($affiliate->commission_rate);
            $owner_rate = 100 - $affiliate_rate;
            
            $affiliate_commission = ($item_total * $affiliate_rate) / 100;
            $owner_commission = ($item_total * $owner_rate) / 100;
            
            // Record the sale
            $sale_data = array(
                'order_id' => $order_id,
                'order_item_id' => $item_id,
                'product_id' => $product_id,
                'vendor_id' => $product_owner_id,
                'product_owner_id' => $product_owner_id,
                'affiliate_vendor_id' => $affiliate_vendor_id,
                'product_price' => $product->get_price(),
                'product_quantity' => $item_qty,
                'product_total' => $item_total,
                'owner_commission' => $owner_commission,
                'affiliate_commission' => $affiliate_commission,
                'commission_rate' => $affiliate_rate,
                'order_status' => $order->get_status(),
                'commission_status' => 'pending',
                'store_origin' => $affiliate_vendor_id,
                'tracking_data' => array(
                    'item_name' => $item->get_name(),
                    'order_date' => $order->get_date_created()->date('Y-m-d H:i:s')
                )
            );
            
            // Save to database
            WCFM_Affiliate()->db->record_sale($sale_data);
            
            // Add meta to order item
            $item->add_meta_data('_affiliate_vendor_id', $affiliate_vendor_id, true);
            $item->add_meta_data('_affiliate_commission', $affiliate_commission, true);
            $item->add_meta_data('_owner_commission', $owner_commission, true);
            $item->save();
            
            // Log for debugging
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf(
                    'Affiliate Sale: Order #%d, Product #%d, Owner: %d (%.2f), Affiliate: %d (%.2f)',
                    $order_id,
                    $product_id,
                    $product_owner_id,
                    $owner_commission,
                    $affiliate_vendor_id,
                    $affiliate_commission
                ));
            }
        }
    }
    
    /**
     * Update commission status on order status change
     */
    public function update_commission_status($order_id, $old_status, $new_status, $order) {
        global $wpdb;
        
        $table = $wpdb->prefix . WCFM_Affiliate_DB::TABLE_SALES;
        
        // Update order status
        $wpdb->update(
            $table,
            array('order_status' => $new_status),
            array('order_id' => $order_id)
        );
        
        // Update commission status based on order status
        $commission_status = 'pending';
        
        $completed_statuses = array('completed', 'processing');
        $cancelled_statuses = array('cancelled', 'refunded', 'failed');
        
        if (in_array($new_status, $completed_statuses)) {
            $commission_status = 'completed';
        } elseif (in_array($new_status, $cancelled_statuses)) {
            $commission_status = 'cancelled';
        }
        
        $wpdb->update(
            $table,
            array('commission_status' => $commission_status),
            array('order_id' => $order_id)
        );
    }
    
    /**
     * Modify WCFM commission calculation for affiliate products
     */
    public function modify_wcfm_commission($order_item_id, $order_id, $product_id, $quantity, $item_sub_total, $item_total, $item_tax, $shipping, $shipping_tax) {
        // Get order item
        $order_item = new WC_Order_Item_Product($order_item_id);
        
        // Check if it's an affiliate sale
        $affiliate_vendor_id = $order_item->get_meta('_affiliate_vendor_id', true);
        
        if ($affiliate_vendor_id) {
            // This product was sold through affiliate
            // The commission calculation will be handled separately
            
            // You can modify WCFM commission here if needed
            // For now, we let WCFM handle the owner commission normally
        }
        
        return $order_item_id;
    }
    
    /**
     * Add affiliate commission to withdrawal calculation
     */
    public function add_affiliate_to_withdrawal($amount, $vendor_id, $order_id) {
        global $wpdb;
        
        // Get affiliate sales for this vendor and order
        $table = $wpdb->prefix . WCFM_Affiliate_DB::TABLE_SALES;
        
        $affiliate_commission = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(affiliate_commission) FROM {$table} 
            WHERE affiliate_vendor_id = %d AND order_id = %d AND commission_status = 'completed'",
            $vendor_id,
            $order_id
        ));
        
        if ($affiliate_commission) {
            $amount += floatval($affiliate_commission);
        }
        
        return $amount;
    }
    
    /**
     * Calculate commission for affiliate sale
     */
    public function calculate_commission($product_total, $commission_rate) {
        return ($product_total * $commission_rate) / 100;
    }
    
    /**
     * Get vendor affiliate earnings
     */
    public function get_vendor_earnings($vendor_id, $status = 'completed') {
        global $wpdb;
        
        $table = $wpdb->prefix . WCFM_Affiliate_DB::TABLE_SALES;
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_sales,
                SUM(affiliate_commission) as total_earnings,
                SUM(product_total) as total_sales_value
            FROM {$table}
            WHERE affiliate_vendor_id = %d AND commission_status = %s",
            $vendor_id,
            $status
        ));
        
        return $result;
    }
    
    /**
     * Get product owner earnings from affiliate sales
     */
    public function get_owner_earnings($vendor_id, $status = 'completed') {
        global $wpdb;
        
        $table = $wpdb->prefix . WCFM_Affiliate_DB::TABLE_SALES;
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_sales,
                SUM(owner_commission) as total_earnings,
                SUM(product_total) as total_sales_value
            FROM {$table}
            WHERE product_owner_id = %d AND commission_status = %s",
            $vendor_id,
            $status
        ));
        
        return $result;
    }
}

