<?php
/**
 * Gestión de Estado de Productos Afiliados
 * 
 * Oculta automáticamente productos afiliados cuando el producto original
 * cambia de estado a borrador, pendiente, o cualquier estado que no sea "publish"
 * 
 * @package WCFM_Product_Affiliate
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCFM_Affiliate_Product_Status {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Hook cuando cambia el estado de un producto
        add_action('transition_post_status', array($this, 'check_product_status_change'), 10, 3);
        
        // Hook para ocultar productos no disponibles en consultas
        add_action('pre_get_posts', array($this, 'hide_unavailable_affiliates'), 20);
        
        // Hook para mostrar mensaje en listado de productos del vendor
        add_filter('wcfm_product_manage_fields_general', array($this, 'add_unavailable_notice'), 10, 3);
        
        // Shortcode para mostrar estado del producto original
        add_shortcode('producto_afiliado_estado', array($this, 'shortcode_product_status'));
    }
    
    /**
     * Verificar cambio de estado del producto
     * 
     * @param string $new_status Nuevo estado
     * @param string $old_status Estado anterior
     * @param WP_Post $post Post object
     */
    public function check_product_status_change($new_status, $old_status, $post) {
        // Solo procesar productos
        if ($post->post_type !== 'product') {
            return;
        }
        
        // Si el estado cambió
        if ($new_status !== $old_status) {
            error_log('🔄 WCFM Affiliate Status: Producto #' . $post->ID . ' cambió de "' . $old_status . '" a "' . $new_status . '"');
            
            global $wpdb;
            $table = $wpdb->prefix . WCFM_Affiliate_DB::TABLE_AFFILIATES;
            
            // Verificar si este producto tiene afiliados
            $affiliates_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE product_id = %d",
                $post->ID
            ));
            
            if ($affiliates_count > 0) {
                error_log('📊 WCFM Affiliate Status: Producto tiene ' . $affiliates_count . ' afiliaciones');
                
                // Actualizar campo de estado activo
                if ($new_status === 'publish') {
                    // Producto publicado: marcar como activo
                    $wpdb->update(
                        $table,
                        array('is_active' => 1),
                        array('product_id' => $post->ID),
                        array('%d'),
                        array('%d')
                    );
                    error_log('✅ WCFM Affiliate Status: Afiliaciones ACTIVADAS para producto #' . $post->ID);
                } else {
                    // Producto en cualquier otro estado: marcar como inactivo
                    $wpdb->update(
                        $table,
                        array('is_active' => 0),
                        array('product_id' => $post->ID),
                        array('%d'),
                        array('%d')
                    );
                    error_log('❌ WCFM Affiliate Status: Afiliaciones DESACTIVADAS para producto #' . $post->ID . ' (estado: ' . $new_status . ')');
                }
            }
        }
    }
    
    /**
     * Ocultar productos afiliados no disponibles en escaparates
     * 
     * @param WP_Query $query
     */
    public function hide_unavailable_affiliates($query) {
        // Solo en frontend y para productos
        if (is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'product') {
            return;
        }
        
        // Solo en tiendas y catálogos
        if (!is_shop() && !is_product_category() && !is_product_tag() && !function_exists('wcfmmp_is_store_page')) {
            return;
        }
        
        if (function_exists('wcfmmp_is_store_page') && !wcfmmp_is_store_page()) {
            return;
        }
        
        // Añadir meta_query para excluir productos afiliados inactivos
        $meta_query = $query->get('meta_query', array());
        
        $meta_query[] = array(
            'relation' => 'OR',
            array(
                'key' => '_is_affiliate_product',
                'compare' => 'NOT EXISTS'
            ),
            array(
                'relation' => 'AND',
                array(
                    'key' => '_is_affiliate_product',
                    'value' => '1',
                    'compare' => '='
                ),
                array(
                    'key' => '_affiliate_is_active',
                    'value' => '1',
                    'compare' => '='
                )
            )
        );
        
        $query->set('meta_query', $meta_query);
    }
    
    /**
     * Añadir aviso en listado de productos del vendor si el producto está inactivo
     * 
     * @param array $fields Campos del formulario
     * @param int $product_id ID del producto
     * @param int $product_vendor_id ID del vendor
     * @return array
     */
    public function add_unavailable_notice($fields, $product_id, $product_vendor_id) {
        // Verificar si es un producto afiliado
        $is_affiliate = get_post_meta($product_id, '_is_affiliate_product', true);
        
        if ($is_affiliate !== '1') {
            return $fields;
        }
        
        // Verificar si está activo
        $is_active = get_post_meta($product_id, '_affiliate_is_active', true);
        
        if ($is_active === '0' || $is_active === 0) {
            // Producto afiliado inactivo
            $original_id = get_post_meta($product_id, '_original_product_id', true);
            $original_product = $original_id ? wc_get_product($original_id) : null;
            
            $notice = '<div class="wcfm-message wcfm-warning" style="margin: 15px 0; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">';
            $notice .= '<i class="fas fa-exclamation-triangle" style="color: #ff9800; margin-right: 10px;"></i>';
            $notice .= '<strong>Producto Temporalmente No Disponible</strong><br>';
            $notice .= '<span style="font-size: 13px; color: #666;">El producto original no está publicado actualmente';
            
            if ($original_product) {
                $notice .= ' (Estado: <strong>' . ucfirst($original_product->get_status()) . '</strong>)';
            }
            
            $notice .= '. Este producto no se mostrará en tu escaparate hasta que el propietario original lo vuelva a publicar.</span>';
            $notice .= '</div>';
            
            // Añadir al inicio del formulario
            if (isset($fields['general'])) {
                array_unshift($fields['general'], array(
                    'unavailable_notice' => array(
                        'type' => 'html',
                        'value' => $notice
                    )
                ));
            }
        }
        
        return $fields;
    }
    
    /**
     * Shortcode para mostrar estado del producto original
     * Uso: [producto_afiliado_estado product_id="123"]
     */
    public function shortcode_product_status($atts) {
        $atts = shortcode_atts(array(
            'product_id' => 0
        ), $atts);
        
        $product_id = intval($atts['product_id']);
        
        if (!$product_id) {
            return '';
        }
        
        $is_affiliate = get_post_meta($product_id, '_is_affiliate_product', true);
        
        if ($is_affiliate !== '1') {
            return '';
        }
        
        $is_active = get_post_meta($product_id, '_affiliate_is_active', true);
        
        if ($is_active === '0' || $is_active === 0) {
            $original_id = get_post_meta($product_id, '_original_product_id', true);
            $original_product = $original_id ? wc_get_product($original_id) : null;
            
            $output = '<div class="affiliate-product-unavailable" style="background: #fff3cd; border: 2px solid #ffc107; border-radius: 8px; padding: 20px; margin: 20px 0; text-align: center;">';
            $output .= '<i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #ff9800; margin-bottom: 15px;"></i>';
            $output .= '<h3 style="margin: 10px 0; color: #856404;">Producto Temporalmente No Disponible</h3>';
            $output .= '<p style="margin: 10px 0; color: #666;">Este producto no está disponible para la venta en este momento.</p>';
            
            if ($original_product) {
                $output .= '<p style="font-size: 14px; color: #999;">Estado del producto original: <strong>' . ucfirst($original_product->get_status()) . '</strong></p>';
            }
            
            $output .= '</div>';
            
            return $output;
        }
        
        return '';
    }
    
    /**
     * Actualizar campo is_active en la tabla de afiliaciones
     * Se ejecuta al crear la afiliación
     * 
     * @param int $product_id ID del producto original
     * @param int $affiliate_vendor_id ID del vendor afiliado
     */
    public static function set_initial_status($product_id, $affiliate_vendor_id) {
        global $wpdb;
        
        // Verificar estado del producto original
        $product = wc_get_product($product_id);
        if (!$product) {
            return;
        }
        
        $is_active = ($product->get_status() === 'publish') ? 1 : 0;
        
        // Actualizar en la tabla
        $table = $wpdb->prefix . WCFM_Affiliate_DB::TABLE_AFFILIATES;
        
        $wpdb->update(
            $table,
            array('is_active' => $is_active),
            array(
                'product_id' => $product_id,
                'affiliate_vendor_id' => $affiliate_vendor_id
            ),
            array('%d'),
            array('%d', '%d')
        );
        
        error_log('📝 WCFM Affiliate Status: Estado inicial configurado - Producto: #' . $product_id . ' - Afiliado: #' . $affiliate_vendor_id . ' - Activo: ' . $is_active);
    }
}

// Inicializar
new WCFM_Affiliate_Product_Status();

