<?php
/**
 * Admin handler for WCFM Product Affiliate
 *
 * @package WCFM_Product_Affiliate
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCFM_Affiliate_Admin {
    
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
        // Add settings page to WCFM
        add_action('wcfm_settings_form_marketplace_end', array($this, 'add_settings_fields'), 10);
        
        // Save settings
        add_action('wcfm_settings_update', array($this, 'save_settings'), 10);
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    
    /**
     * Add settings fields to WCFM Marketplace settings
     */
    public function add_settings_fields() {
        global $WCFM;
        
        $options = WCFM_Affiliate()->get_option();
        
        ?>
        <!-- collapsible -->
        <div class="page_collapsible" id="wcfm_settings_form_affiliate_head">
            <label class="wcfmfa fa-handshake-o"></label>
            Configuración de Afiliación<span></span>
        </div>
        <div class="wcfm-container">
            <div id="wcfm_settings_form_affiliate_expander" class="wcfm-content">
                <h2>Configuración de Afiliación de Productos</h2>
                <div class="wcfm_clearfix"></div>
                <?php
                $WCFM->wcfm_fields->wcfm_generate_form_field(array(
                    "affiliate_enabled" => array(
                        'label' => 'Activar Sistema de Afiliación',
                        'type' => 'checkbox',
                        'class' => 'wcfm-checkbox wcfm_ele',
                        'label_class' => 'wcfm_title checkbox_title',
                        'value' => 'yes',
                        'dfvalue' => isset($options['enabled']) ? $options['enabled'] : 'yes',
                        'desc' => 'Permitir a los vendedores vender productos de otros vendedores como afiliados.'
                    ),
                    "affiliate_default_commission" => array(
                        'label' => 'Comisión de Afiliado por Defecto (%)',
                        'type' => 'number',
                        'class' => 'wcfm-text wcfm_ele',
                        'label_class' => 'wcfm_title',
                        'value' => isset($options['default_commission_rate']) ? $options['default_commission_rate'] : 1,
                        'attributes' => array('min' => 1, 'max' => 100, 'step' => 0.1),
                        'desc' => 'Tasa de comisión por defecto para vendedores afiliados (porcentaje de venta).'
                    ),
                    "affiliate_disable_multivendor" => array(
                        'label' => 'Desactivar Clonación de Productos',
                        'type' => 'checkbox',
                        'class' => 'wcfm-checkbox wcfm_ele',
                        'label_class' => 'wcfm_title checkbox_title',
                        'value' => 'yes',
                        'dfvalue' => isset($options['disable_product_multivendor']) ? $options['disable_product_multivendor'] : 'yes',
                        'desc' => 'Desactivar la funcionalidad original de clonación multivendedor de productos.'
                    ),
                ));
                ?>
                <div class="wcfm_clearfix"></div>
            </div>
        </div>
        <div class="wcfm_clearfix"></div>
        <!-- end collapsible -->
        <?php
    }
    
    /**
     * Save settings
     */
    public function save_settings($wcfm_settings_form) {
        $options = WCFM_Affiliate()->get_option();
        
        // Enable/disable
        if (isset($wcfm_settings_form['affiliate_enabled'])) {
            $options['enabled'] = 'yes';
        } else {
            $options['enabled'] = 'no';
        }
        
        // Default commission
        if (isset($wcfm_settings_form['affiliate_default_commission'])) {
            $options['default_commission_rate'] = intval($wcfm_settings_form['affiliate_default_commission']);
        }
        
        // Disable multivendor
        if (isset($wcfm_settings_form['affiliate_disable_multivendor'])) {
            $options['disable_product_multivendor'] = 'yes';
        } else {
            $options['disable_product_multivendor'] = 'no';
        }
        
        update_option('wcfm_affiliate_options', $options);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'wcfm',
            'Ventas por Afiliación',
            'Ventas por Afiliación',
            'manage_woocommerce',
            'wcfm-affiliate-sales',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1>Reporte de Ventas por Afiliación</h1>
            
            <div class="wcfm-affiliate-stats" style="margin: 20px 0;">
                <?php
                global $wpdb;
                $table = $wpdb->prefix . WCFM_Affiliate_DB::TABLE_SALES;
                
                $stats = $wpdb->get_row("
                    SELECT 
                        COUNT(DISTINCT affiliate_vendor_id) as total_affiliates,
                        COUNT(*) as total_sales,
                        SUM(affiliate_commission) as total_affiliate_earnings,
                        SUM(owner_commission) as total_owner_earnings,
                        SUM(product_total) as total_sales_value
                    FROM {$table}
                    WHERE commission_status = 'completed'
                ");
                ?>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                        <h3>Total Afiliados</h3>
                        <p style="font-size: 24px; font-weight: bold; margin: 0;"><?php echo intval($stats->total_affiliates); ?></p>
                    </div>
                    
                    <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                        <h3>Total Ventas</h3>
                        <p style="font-size: 24px; font-weight: bold; margin: 0;"><?php echo intval($stats->total_sales); ?></p>
                    </div>
                    
                    <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                        <h3>Ganancias Afiliados</h3>
                        <p style="font-size: 24px; font-weight: bold; margin: 0; color: #2ecc71;"><?php echo wc_price($stats->total_affiliate_earnings); ?></p>
                    </div>
                    
                    <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                        <h3>Ganancias Propietarios</h3>
                        <p style="font-size: 24px; font-weight: bold; margin: 0; color: #3498db;"><?php echo wc_price($stats->total_owner_earnings); ?></p>
                    </div>
                    
                    <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                        <h3>Valor Total Ventas</h3>
                        <p style="font-size: 24px; font-weight: bold; margin: 0;"><?php echo wc_price($stats->total_sales_value); ?></p>
                    </div>
                </div>
            </div>
            
            <h2>Ventas Recientes por Afiliación</h2>
            
            <?php
            $recent_sales = $wpdb->get_results("
                SELECT s.*, p.post_title as product_name
                FROM {$table} s
                LEFT JOIN {$wpdb->posts} p ON s.product_id = p.ID
                ORDER BY s.created_at DESC
                LIMIT 50
            ");
            ?>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>Producto</th>
                        <th>Afiliado</th>
                        <th>Propietario</th>
                        <th>Total</th>
                        <th>Com. Afiliado</th>
                        <th>Com. Propietario</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_sales as $sale): ?>
                        <?php
                        $affiliate = get_userdata($sale->affiliate_vendor_id);
                        $owner = get_userdata($sale->product_owner_id);
                        ?>
                        <tr>
                            <td><a href="<?php echo admin_url('post.php?post=' . $sale->order_id . '&action=edit'); ?>">#<?php echo $sale->order_id; ?></a></td>
                            <td><?php echo esc_html($sale->product_name); ?></td>
                            <td><?php echo $affiliate ? esc_html($affiliate->display_name) : '-'; ?></td>
                            <td><?php echo $owner ? esc_html($owner->display_name) : '-'; ?></td>
                            <td><?php echo wc_price($sale->product_total); ?></td>
                            <td><?php echo wc_price($sale->affiliate_commission); ?></td>
                            <td><?php echo wc_price($sale->owner_commission); ?></td>
                            <td><?php echo esc_html($sale->commission_status); ?></td>
                            <td><?php echo date_i18n(get_option('date_format'), strtotime($sale->created_at)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}

