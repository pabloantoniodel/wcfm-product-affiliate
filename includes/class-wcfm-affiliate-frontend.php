<?php
/**
 * Frontend handler for WCFM Product Affiliate
 *
 * @package WCFM_Product_Affiliate
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCFM_Affiliate_Frontend {
    
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
        // Disable original multivendor clone functionality
        if (WCFM_Affiliate()->get_option('disable_product_multivendor', 'yes') === 'yes') {
            add_filter('wcfmmp_is_allow_single_product_multivendor', '__return_false', 999);
        }
        
        // Add affiliate button to product page
        add_action('woocommerce_single_product_summary', array($this, 'add_affiliate_button'), 36);
        
        // Add WCFM menu
        add_filter('wcfm_menus', array($this, 'add_wcfm_menu'), 30);
        
        // Add WCFM query vars
        add_filter('wcfm_query_vars', array($this, 'add_query_vars'), 20);
        
        // Add WCFM endpoint title
        add_filter('wcfm_endpoint_title', array($this, 'add_endpoint_title'), 20, 2);
        
        // Initialize endpoint
        add_action('init', array($this, 'init_endpoint'), 20);
        
        // Endpoint slug customization
        add_filter('wcfm_endpoints_slug', array($this, 'endpoint_slug'));
        
        // Load WCFM views
        add_action('wcfm_load_views', array($this, 'load_views'), 30);
        
        // Load scripts
        add_action('wcfm_load_scripts', array($this, 'load_scripts'), 30);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('wcfm_load_scripts', array($this, 'load_mobile_menu_fix'), 40);
        
        // Deshabilitar GMW location form script en páginas WCFM para evitar error de Google Maps
        add_action('wp_enqueue_scripts', array($this, 'disable_gmw_in_wcfm'), 999);
        
        // AJAX handlers
        add_action('wp_ajax_wcfm_affiliate_add_product', array($this, 'ajax_add_affiliate'));
        add_action('wp_ajax_wcfm_affiliate_remove_product', array($this, 'ajax_remove_affiliate'));
        add_action('wp_ajax_wcfm_affiliate_bulk_add', array($this, 'ajax_bulk_add'));
        add_action('wp_ajax_wcfm_affiliate_hide_instructions', array($this, 'ajax_hide_instructions'));
        
        // Add affiliate products to vendor store
        add_action('pre_get_posts', array($this, 'add_affiliates_to_store_query'), 999);
        
        // Add affiliate reference to product links in store
        add_filter('post_link', array($this, 'add_affiliate_ref_to_product_link'), 10, 2);
        add_filter('woocommerce_loop_product_link', array($this, 'add_affiliate_ref_to_loop_link'), 10, 2);
        
        // Add affiliate badge to products in store
        add_action('woocommerce_before_shop_loop_item_title', array($this, 'add_affiliate_badge'), 15);
        
        // Show affiliate info on single product page
        add_action('woocommerce_single_product_summary', array($this, 'show_affiliate_info'), 6);
    }
    
    /**
     * Add affiliate button to product page
     */
    public function add_affiliate_button() {
        global $WCFMmp, $product;
        
        // Only for vendors
        if (!wcfm_is_vendor()) {
            return;
        }
        
        if (!$product || !method_exists($product, 'get_id')) {
            return;
        }
        
        $product_id = $product->get_id();
        $vendor_id = $WCFMmp->vendor_id;
        $product_owner = get_post_field('post_author', $product_id);
        
        // Don't show if vendor is the owner
        if ($vendor_id == $product_owner) {
            return;
        }
        
        // Check if already has affiliate
        $has_affiliate = WCFM_Affiliate()->db->has_affiliate($vendor_id, $product_id);
        
        // Get button style
        $button_style = '';
        $wcfm_store_color_settings = get_option('wcfm_store_color_settings', array());
        
        if (isset($wcfm_store_color_settings['button_bg'])) {
            $button_style .= 'background: ' . $wcfm_store_color_settings['button_bg'] . ';';
        }
        if (isset($wcfm_store_color_settings['button_text'])) {
            $button_style .= 'color: ' . $wcfm_store_color_settings['button_text'] . ';';
        }
        
        ?>
        <div class="wcfm_affiliate_button_wrapper" style="margin: 15px 0;">
            <?php if ($has_affiliate): ?>
                <a href="#" class="wcfm_affiliate_remove_button" data-product-id="<?php echo esc_attr($product_id); ?>" style="<?php echo esc_attr($button_style); ?> padding: 10px 20px; display: inline-block; text-decoration: none; border-radius: 3px;">
                    <span class="dashicons dashicons-no" style="vertical-align: middle;"></span>
                    Quitar de Mi Tienda
                </a>
            <?php else: ?>
                <a href="#" class="wcfm_affiliate_add_button" data-product-id="<?php echo esc_attr($product_id); ?>" style="<?php echo esc_attr($button_style); ?> padding: 10px 20px; display: inline-block; text-decoration: none; border-radius: 3px;">
                    <span class="dashicons dashicons-store" style="vertical-align: middle;"></span>
                    Vender Este Producto
                </a>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Add WCFM menu
     */
    public function add_wcfm_menu($menus) {
        if (!wcfm_is_vendor()) {
            return $menus;
        }
        
        $menus = array_slice($menus, 0, 3, true) +
            array(
                'wcfm-affiliate-products' => array(
                    'label' => 'Productos Afiliados',
                    'url' => wcfm_get_endpoint_url('wcfm-affiliate-products', '', get_wcfm_page()),
                    'icon' => 'handshake-o',
                    'menu_for' => 'vendor',
                    'priority' => 65
                )
            ) +
            array_slice($menus, 3, count($menus) - 3, true);
        
        return $menus;
    }
    
    /**
     * Add query vars
     */
    public function add_query_vars($query_vars) {
        $wcfm_modified_endpoints = wcfm_get_option('wcfm_endpoints', array());
        
        $query_affiliate_vars = array(
            'wcfm-affiliate-products' => !empty($wcfm_modified_endpoints['wcfm-affiliate-products']) ? $wcfm_modified_endpoints['wcfm-affiliate-products'] : 'affiliate-products',
        );
        
        $query_vars = array_merge($query_vars, $query_affiliate_vars);
        
        return $query_vars;
    }
    
    /**
     * Add endpoint title
     */
    public function add_endpoint_title($title, $endpoint) {
        if ($endpoint === 'wcfm-affiliate-products') {
            $title = 'Productos Afiliados';
        }
        return $title;
    }
    
    /**
     * Initialize endpoint
     */
    public function init_endpoint() {
        global $WCFM_Query;
        
        if ($WCFM_Query) {
            // Initialize WCFM endpoints
            $WCFM_Query->init_query_vars();
            $WCFM_Query->add_endpoints();
            
            // Flush rewrite rules if needed
            if (!get_option('wcfm_affiliate_endpoint_added')) {
                flush_rewrite_rules();
                update_option('wcfm_affiliate_endpoint_added', 1);
            }
        }
    }
    
    /**
     * Customize endpoint slug
     */
    public function endpoint_slug($endpoints) {
        $endpoints['wcfm-affiliate-products'] = 'affiliate-products';
        return $endpoints;
    }
    
    /**
     * Load views
     */
    public function load_views($end_point) {
        if ($end_point === 'wcfm-affiliate-products') {
            include WCFM_AFFILIATE_PLUGIN_DIR . 'frontend/views/affiliate-catalog.php';
        }
    }
    
    /**
     * Load scripts
     */
    public function load_scripts($end_point) {
        if ($end_point === 'wcfm-affiliate-products') {
            wp_enqueue_script('wcfm-affiliate-catalog', WCFM_AFFILIATE_PLUGIN_URL . 'frontend/assets/js/affiliate.js', array('jquery'), WCFM_AFFILIATE_VERSION, true);
            
            wp_localize_script('wcfm-affiliate-catalog', 'wcfm_affiliate_params', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wcfm_affiliate_nonce'),
                'i18n' => array(
                    'confirm_add' => '¿Añadir este producto a tu tienda?',
                    'confirm_remove' => '¿Eliminar este producto de tu tienda?',
                    'success_add' => '¡Producto añadido correctamente!',
                    'success_remove' => '¡Producto eliminado correctamente!',
                    'error' => 'Ocurrió un error. Por favor intenta de nuevo.',
                    'select_products' => 'Por favor selecciona al menos un producto'
                )
            ));
        }
    }
    
    /**
     * Load mobile menu fix script
     */
    public function load_mobile_menu_fix($end_point) {
        // Cargar CSS para menú móvil
        wp_enqueue_style('wcfm-mobile-menu-fix-css', WCFM_AFFILIATE_PLUGIN_URL . 'frontend/assets/css/mobile-menu-fix.css', array(), WCFM_AFFILIATE_VERSION);
        
        // Cargar JS en todas las páginas de WCFM con versión actualizada
        wp_enqueue_script('wcfm-mobile-menu-fix', WCFM_AFFILIATE_PLUGIN_URL . 'frontend/assets/js/mobile-menu-fix.js', array('jquery'), '1.0.2', true);
    }
    
    /**
     * Deshabilitar GMW location form script en páginas WCFM
     * Esto previene el error: "Uncaught ReferenceError: google is not defined"
     */
    public function disable_gmw_in_wcfm() {
        global $wp;
        
        // Detectar si estamos en una página de WCFM
        $is_wcfm_page = false;
        
        // Verificar si la URL contiene 'store-manager'
        if (isset($wp->request) && strpos($wp->request, 'store-manager') !== false) {
            $is_wcfm_page = true;
        }
        
        // O si estamos en un endpoint de WCFM
        if (function_exists('wcfm_get_option') && isset($wp->query_vars) && !empty($wp->query_vars)) {
            $wcfm_endpoints = array('wcfm-affiliate-products', 'wcfm-products', 'wcfm-orders', 'wcfm-dashboard');
            foreach ($wcfm_endpoints as $endpoint) {
                if (isset($wp->query_vars[$endpoint])) {
                    $is_wcfm_page = true;
                    break;
                }
            }
        }
        
        // Si es página WCFM, deshabilitar GMW location form
        if ($is_wcfm_page) {
            wp_dequeue_script('gmw-location-form');
            wp_deregister_script('gmw-location-form');
        }
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_frontend_scripts() {
        if (!is_product()) {
            return;
        }
        
        wp_enqueue_script('wcfm-affiliate-button', WCFM_AFFILIATE_PLUGIN_URL . 'frontend/assets/js/affiliate.js', array('jquery'), WCFM_AFFILIATE_VERSION, true);
        
        wp_localize_script('wcfm-affiliate-button', 'wcfm_affiliate_params', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wcfm_affiliate_nonce'),
            'i18n' => array(
                'confirm_add' => '¿Añadir este producto a tu tienda?',
                'confirm_remove' => '¿Eliminar este producto de tu tienda?',
                'success_add' => '¡Producto añadido correctamente!',
                'success_remove' => '¡Producto eliminado correctamente!',
                'error' => 'Ocurrió un error. Por favor intenta de nuevo.',
                'select_products' => 'Por favor selecciona al menos un producto'
            )
        ));
    }
    
    /**
     * AJAX: Add affiliate product
     */
    public function ajax_add_affiliate() {
        check_ajax_referer('wcfm_affiliate_nonce', 'nonce');
        
        if (!wcfm_is_vendor()) {
            wp_send_json_error(array('message' => 'No autorizado'));
        }
        
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $vendor_id = get_current_user_id();
        
        if (!$product_id) {
            wp_send_json_error(array('message' => 'Producto inválido'));
        }
        
        // Add affiliate
        $result = WCFM_Affiliate()->db->add_affiliate($vendor_id, $product_id);
        
        if ($result) {
            wp_send_json_success(array('message' => '¡Producto añadido correctamente!'));
        } else {
            wp_send_json_error(array('message' => 'Error al añadir el producto'));
        }
    }
    
    /**
     * AJAX: Remove affiliate product
     */
    public function ajax_remove_affiliate() {
        check_ajax_referer('wcfm_affiliate_nonce', 'nonce');
        
        if (!wcfm_is_vendor()) {
            wp_send_json_error(array('message' => 'No autorizado'));
        }
        
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $vendor_id = get_current_user_id();
        
        if (!$product_id) {
            wp_send_json_error(array('message' => 'Producto inválido'));
        }
        
        // Remove affiliate
        $result = WCFM_Affiliate()->db->remove_affiliate($vendor_id, $product_id);
        
        if ($result !== false) {
            wp_send_json_success(array('message' => '¡Producto eliminado correctamente!'));
        } else {
            wp_send_json_error(array('message' => 'Error al eliminar el producto'));
        }
    }
    
    /**
     * AJAX: Bulk add affiliates
     */
    public function ajax_bulk_add() {
        check_ajax_referer('wcfm_affiliate_nonce', 'nonce');
        
        if (!wcfm_is_vendor()) {
            wp_send_json_error(array('message' => 'No autorizado'));
        }
        
        $product_ids = isset($_POST['product_ids']) ? array_map('intval', $_POST['product_ids']) : array();
        $vendor_id = get_current_user_id();
        
        if (empty($product_ids)) {
            wp_send_json_error(array('message' => 'No hay productos seleccionados'));
        }
        
        $added = 0;
        foreach ($product_ids as $product_id) {
            if (WCFM_Affiliate()->db->add_affiliate($vendor_id, $product_id)) {
                $added++;
            }
        }
        
        wp_send_json_success(array('message' => sprintf('¡%d productos añadidos correctamente!', $added)));
    }
    
    /**
     * AJAX: Hide/Show instructions
     */
    public function ajax_hide_instructions() {
        check_ajax_referer('wcfm_affiliate_nonce', 'nonce');
        
        if (!wcfm_is_vendor()) {
            wp_send_json_error(array('message' => 'No autorizado'));
        }
        
        $hide = isset($_POST['hide']) && $_POST['hide'] === 'true';
        $user_id = get_current_user_id();
        
        if ($hide) {
            update_user_meta($user_id, '_wcfm_affiliate_hide_instructions', '1');
            wp_send_json_success(array('message' => 'Instrucciones ocultadas'));
        } else {
            delete_user_meta($user_id, '_wcfm_affiliate_hide_instructions');
            wp_send_json_success(array('message' => 'Instrucciones restauradas'));
        }
    }
    
    /**
     * Add affiliate products to vendor store query
     */
    public function add_affiliates_to_store_query($query) {
        // Solo en la tienda del vendedor
        if (!function_exists('wcfm_is_store_page') || !wcfm_is_store_page()) {
            return;
        }
        
        // Solo en query principal de productos
        if (!$query->is_main_query() || $query->get('post_type') !== 'product') {
            return;
        }
        
        global $WCFMmp;
        
        // Obtener el vendedor de la tienda
        $store_name = get_query_var($WCFMmp->wcfm_store_url);
        
        if (empty($store_name)) {
            error_log('🔍 Affiliate Query: store_name vacío');
            return;
        }
        
        $seller_info = get_user_by('slug', $store_name);
        
        if (!$seller_info) {
            error_log('🔍 Affiliate Query: seller_info no encontrado para: ' . $store_name);
            return;
        }
        
        $vendor_id = $seller_info->ID;
        error_log('🔍 Affiliate Query: Procesando tienda: ' . $store_name . ' (ID: ' . $vendor_id . ')');
        
        // Obtener productos afiliados de este vendedor
        $affiliates = WCFM_Affiliate()->db->get_vendor_affiliates($vendor_id, 'active');
        
        error_log('🔍 Affiliate Query: Productos afiliados encontrados: ' . count($affiliates));
        
        // Si no hay afiliados, NO hacer nada - dejar que WCFM maneje normalmente
        if (empty($affiliates)) {
            error_log('✅ Affiliate Query: Sin afiliados, dejando query sin modificar (solo productos propios por author_name)');
            return;
        }
        
        // Extraer IDs de productos afiliados
        $affiliate_product_ids = array();
        foreach ($affiliates as $affiliate) {
            $affiliate_product_ids[] = $affiliate->product_id;
        }
        
        error_log('🔍 Affiliate Query: Productos afiliados: ' . count($affiliate_product_ids));
        
        // Obtener productos propios del vendedor
        $own_products = get_posts(array(
            'post_type' => 'product',
            'author' => $vendor_id,
            'posts_per_page' => -1,
            'fields' => 'ids',
            'post_status' => 'publish'
        ));
        
        error_log('🔍 Affiliate Query: Productos propios: ' . count($own_products));
        
        // Combinar productos propios + afiliados
        $all_product_ids = array_merge($own_products, $affiliate_product_ids);
        
        // Validar que tengamos productos
        if (empty($all_product_ids)) {
            error_log('❌ Affiliate Query: Array vacío después de combinar, abortando');
            return;
        }
        
        error_log('✅ Affiliate Query: Total productos a mostrar: ' . count($all_product_ids));
        error_log('   - IDs: ' . implode(', ', array_slice($all_product_ids, 0, 10)));
        
        // APLICAR post__in con TODOS los productos (propios + afiliados)
        $query->set('post__in', $all_product_ids);
        
        // LIMPIAR author_name porque post__in ya incluye los productos propios
        // Si dejamos author_name, WordPress hace AND y filtra solo productos del autor
        // que estén en post__in, eliminando los afiliados
        $query->set('author_name', '');
        $query->set('author', '');
        
        error_log('✅ post__in aplicado, author_name limpiado - Prioridad 999');
    }
    
    /**
     * Add affiliate reference to product link
     */
    public function add_affiliate_ref_to_product_link($permalink, $post) {
        // Solo en páginas de tienda
        if (!function_exists('wcfm_is_store_page') || !wcfm_is_store_page()) {
            return $permalink;
        }
        
        // Solo para productos
        if ($post->post_type !== 'product') {
            return $permalink;
        }
        
        global $WCFMmp;
        
        // Obtener el vendedor de la tienda actual
        $store_name = get_query_var($WCFMmp->wcfm_store_url);
        
        if (empty($store_name)) {
            return $permalink;
        }
        
        $seller_info = get_user_by('slug', $store_name);
        
        if (!$seller_info) {
            return $permalink;
        }
        
        $vendor_id = $seller_info->ID;
        $product_owner = $post->post_author;
        
        // Si el producto NO es del vendedor actual, es un producto afiliado
        if ($vendor_id != $product_owner) {
            // Verificar que realmente es un producto afiliado
            if (WCFM_Affiliate()->db->has_affiliate($vendor_id, $post->ID)) {
                // Añadir parámetro de referencia
                $permalink = add_query_arg(array(
                    'store_origin' => $vendor_id,
                    'ref' => $store_name
                ), $permalink);
            }
        }
        
        return $permalink;
    }
    
    /**
     * Add affiliate reference to product link in loop
     */
    public function add_affiliate_ref_to_loop_link($permalink, $product) {
        // Solo en páginas de tienda
        if (!function_exists('wcfm_is_store_page') || !wcfm_is_store_page()) {
            return $permalink;
        }
        
        global $WCFMmp;
        
        // Obtener el vendedor de la tienda actual
        $store_name = get_query_var($WCFMmp->wcfm_store_url);
        
        if (empty($store_name)) {
            return $permalink;
        }
        
        $seller_info = get_user_by('slug', $store_name);
        
        if (!$seller_info) {
            return $permalink;
        }
        
        $vendor_id = $seller_info->ID;
        $product_id = $product->get_id();
        $product_owner = get_post_field('post_author', $product_id);
        
        // Si el producto NO es del vendedor actual, es un producto afiliado
        if ($vendor_id != $product_owner) {
            // Verificar que realmente es un producto afiliado
            if (WCFM_Affiliate()->db->has_affiliate($vendor_id, $product_id)) {
                // Añadir parámetro de referencia
                $permalink = add_query_arg(array(
                    'store_origin' => $vendor_id,
                    'ref' => $store_name
                ), $permalink);
            }
        }
        
        return $permalink;
    }
    
    /**
     * Add affiliate badge to products in store loop
     */
    public function add_affiliate_badge() {
        global $product, $WCFMmp;
        
        // Solo en páginas de tienda
        if (!function_exists('wcfm_is_store_page') || !wcfm_is_store_page()) {
            return;
        }
        
        if (!$product) {
            return;
        }
        
        // Obtener el vendedor de la tienda actual
        $store_name = get_query_var($WCFMmp->wcfm_store_url);
        
        if (empty($store_name)) {
            return;
        }
        
        $seller_info = get_user_by('slug', $store_name);
        
        if (!$seller_info) {
            return;
        }
        
        $vendor_id = $seller_info->ID;
        $product_id = $product->get_id();
        $product_owner = get_post_field('post_author', $product_id);
        
        // Si NO es del vendedor actual y es afiliado
        if ($vendor_id != $product_owner && WCFM_Affiliate()->db->has_affiliate($vendor_id, $product_id)) {
            $affiliate = WCFM_Affiliate()->db->get_affiliate($vendor_id, $product_id);
            $owner = get_userdata($product_owner);
            
            echo '<div class="wcfm-affiliate-badge" style="position: absolute; top: 10px; right: 10px; z-index: 10; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 5px 10px; border-radius: 3px; font-size: 11px; font-weight: bold; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">';
            echo '<span class="dashicons dashicons-store" style="font-size: 12px; vertical-align: middle;"></span> ';
            echo 'Producto Afiliado';
            echo '</div>';
        }
    }
    
    /**
     * Show affiliate info on single product page
     */
    public function show_affiliate_info() {
        global $product;
        
        if (!$product) {
            return;
        }
        
        // Verificar si hay parámetro store_origin en la URL
        $store_origin = isset($_GET['store_origin']) ? intval($_GET['store_origin']) : 0;
        $ref_store = isset($_GET['ref']) ? sanitize_text_field($_GET['ref']) : '';
        
        if (!$store_origin || !$ref_store) {
            return;
        }
        
        $product_id = $product->get_id();
        
        // Verificar que es un producto afiliado
        if (!WCFM_Affiliate()->db->has_affiliate($store_origin, $product_id)) {
            return;
        }
        
        $affiliate_vendor = get_userdata($store_origin);
        $product_owner = get_userdata(get_post_field('post_author', $product_id));
        
        if (!$affiliate_vendor || !$product_owner) {
            return;
        }
        
        // Guardar la referencia en la sesión para el tracking
        WCFM_Affiliate()->tracking->set_store_origin($store_origin);
        
        ?>
        <div class="wcfm-affiliate-notice" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(102, 126, 234, 0.3);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <span class="dashicons dashicons-store" style="font-size: 24px;"></span>
                <div>
                    <div style="font-weight: bold; font-size: 14px;">Visto en la tienda de <?php echo esc_html($affiliate_vendor->display_name); ?></div>
                    <div style="font-size: 12px; opacity: 0.9;">Producto de <?php echo esc_html($product_owner->display_name); ?></div>
                </div>
            </div>
        </div>
        <?php
    }
}

