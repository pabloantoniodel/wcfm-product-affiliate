<?php
/**
 * WCFM Product Affiliate - Bloqueador de Productos Afiliados
 * 
 * Bloquea el acceso a productos afiliados y redirige al producto original.
 * Útil para forzar que los clientes compren solo productos originales.
 * 
 * NOTA: Esta funcionalidad está DESACTIVADA por defecto.
 * Para activarla, descomentar la línea en wcfm-product-affiliate.php
 * 
 * @package WCFM_Product_Affiliate
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCFM_Affiliate_Blocker {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Hook para bloquear el acceso a productos afiliados
        add_action('template_redirect', array($this, 'block_affiliate_products'), 5);
        
        // Hook para ocultar productos afiliados de búsquedas y listados
        add_action('pre_get_posts', array($this, 'hide_affiliate_from_listings'));
        
        // Hook para añadir mensaje en admin si está activado
        add_action('admin_notices', array($this, 'admin_notice_blocker_active'));
    }
    
    /**
     * Bloquear acceso a productos afiliados y redirigir al original
     */
    public function block_affiliate_products() {
        // Solo en páginas de producto único
        if (!is_product()) {
            return;
        }
        
        global $post;
        
        if (!$post) {
            return;
        }
        
        $product_id = $post->ID;
        
        // Verificar si es un producto afiliado
        $original_product_id = get_post_meta($product_id, '_wcfm_affiliate_original_product_id', true);
        
        if (!$original_product_id) {
            // No es un producto afiliado, permitir acceso
            return;
        }
        
        // Es un producto afiliado, obtener el producto original
        $original_product = wc_get_product($original_product_id);
        
        if (!$original_product || $original_product->get_status() !== 'publish') {
            // El producto original no existe o no está publicado
            // Mostrar mensaje de error
            wp_die(
                '<h1>' . __('Producto no disponible', 'wcfm-product-affiliate') . '</h1>' .
                '<p>' . __('Este producto afiliado no está disponible actualmente. El producto original no existe o ha sido eliminado.', 'wcfm-product-affiliate') . '</p>' .
                '<p><a href="' . home_url('/tienda/') . '">' . __('Volver a la tienda', 'wcfm-product-affiliate') . '</a></p>',
                __('Producto no disponible', 'wcfm-product-affiliate'),
                array('response' => 404)
            );
        }
        
        // Redirigir al producto original
        $original_url = get_permalink($original_product_id);
        
        error_log('🚫 WCFM Affiliate Blocker: Bloqueando acceso a producto afiliado #' . $product_id);
        error_log('📍 WCFM Affiliate Blocker: Redirigiendo a producto original #' . $original_product_id . ' - ' . $original_url);
        
        wp_redirect($original_url, 301);
        exit;
    }
    
    /**
     * Ocultar productos afiliados de listados y búsquedas
     */
    public function hide_affiliate_from_listings($query) {
        // Solo en frontend y en queries principales
        if (is_admin() || !$query->is_main_query()) {
            return;
        }
        
        // Solo en queries de productos
        if (!isset($query->query_vars['post_type']) || $query->query_vars['post_type'] !== 'product') {
            return;
        }
        
        // Añadir meta query para excluir productos afiliados
        $meta_query = $query->get('meta_query') ?: array();
        
        $meta_query[] = array(
            'key' => '_wcfm_affiliate_original_product_id',
            'compare' => 'NOT EXISTS'
        );
        
        $query->set('meta_query', $meta_query);
        
        error_log('🔍 WCFM Affiliate Blocker: Ocultando productos afiliados de listados');
    }
    
    /**
     * Mostrar aviso en admin cuando el bloqueador está activo
     */
    public function admin_notice_blocker_active() {
        // Solo para administradores
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Solo en página de plugins
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'plugins') {
            return;
        }
        
        ?>
        <div class="notice notice-warning">
            <p>
                <strong>⚠️ WCFM Product Affiliate - Bloqueador Activo:</strong>
                Los productos afiliados están bloqueados. Los clientes serán redirigidos automáticamente a los productos originales.
                Los productos afiliados no aparecerán en búsquedas ni listados.
            </p>
            <p>
                <em>Para desactivar esta funcionalidad, comenta la línea correspondiente en wcfm-product-affiliate.php</em>
            </p>
        </div>
        <?php
    }
    
    /**
     * Método estático para verificar si un producto es afiliado
     * Útil para otros plugins o temas
     * 
     * @param int $product_id ID del producto
     * @return bool True si es afiliado, False si no
     */
    public static function is_affiliate_product($product_id) {
        $original_id = get_post_meta($product_id, '_wcfm_affiliate_original_product_id', true);
        return !empty($original_id);
    }
    
    /**
     * Método estático para obtener el ID del producto original
     * 
     * @param int $affiliate_product_id ID del producto afiliado
     * @return int|false ID del producto original o false si no es afiliado
     */
    public static function get_original_product_id($affiliate_product_id) {
        $original_id = get_post_meta($affiliate_product_id, '_wcfm_affiliate_original_product_id', true);
        return $original_id ? intval($original_id) : false;
    }
}

// NOTA: No inicializar aquí. Se inicializa desde el archivo principal solo si está activado.








