<?php
/**
 * Clasificación de Vendedores
 * 
 * Permite clasificar a los vendedores como "Comercio" y "Comercial"
 * 
 * @package WCFM_Product_Affiliate
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

error_log('📦 WCFM Classification: Archivo cargado');

class WCFM_Affiliate_Vendor_Classification {
    
    /**
     * Constructor
     */
    public function __construct() {
        error_log('🏗️ WCFM Classification: Constructor llamado');
        
        // Añadir menú de administración (después del Bulk Manager que tiene prioridad 10)
        add_action('admin_menu', array($this, 'add_admin_menu'), 25);
        
        // Encolar scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_wcfm_search_vendors_classification', array($this, 'ajax_search_vendors'));
        add_action('wp_ajax_wcfm_update_vendor_classification', array($this, 'ajax_update_classification'));
    }
    
    /**
     * Añadir menú de administración
     */
    public function add_admin_menu() {
        error_log('🔧 WCFM Classification: Registrando menú...');
        
        $hook = add_submenu_page(
            'wcfm-affiliate-bulk',
            'Clasificación de Clientes',
            'Clasificación de Clientes',
            'manage_woocommerce',
            'clasificacion-clientes',
            array($this, 'render_page')
        );
        
        error_log('🔧 WCFM Classification: Hook registrado = ' . ($hook ? $hook : 'NULL'));
    }
    
    /**
     * Encolar scripts y estilos
     */
    public function enqueue_scripts($hook) {
        error_log('🎨 WCFM Classification: enqueue_scripts called - Hook: ' . $hook);
        
        // El hook correcto es: productos-afiliados_page_clasificacion-clientes
        if ($hook !== 'productos-afiliados_page_clasificacion-clientes') {
            error_log('⏭️ WCFM Classification: Hook no coincide, saltando...');
            return;
        }
        
        error_log('✅ WCFM Classification: Cargando CSS y JS...');
        
        wp_enqueue_style(
            'wcfm-vendor-classification',
            plugins_url('admin/assets/css/vendor-classification.css', dirname(__FILE__)),
            array(),
            '1.3.0'
        );
        
        wp_enqueue_script(
            'wcfm-vendor-classification',
            plugins_url('admin/assets/js/vendor-classification.js', dirname(__FILE__)),
            array('jquery'),
            '1.3.0',
            true
        );
        
        wp_localize_script('wcfm-vendor-classification', 'wcfmVendorClassification', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wcfm_vendor_classification_nonce')
        ));
    }
    
    /**
     * Renderizar página principal
     */
    public function render_page() {
        ?>
        <div class="wrap wcfm-vendor-classification-wrap">
            <h1 class="wp-heading-inline">
                <i class="fas fa-users-cog"></i>
                Clasificación de Clientes
            </h1>
            
            <p class="description">
                Clasifica a tus vendedores como "Comercio" y/o "Comercial". Por defecto, todos los vendedores tienen ambas clasificaciones activas.
            </p>
            
            <div class="wcfm-classification-container">
                
                <!-- Buscador -->
                <div class="wcfm-classification-search">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input 
                            type="text" 
                            id="vendor-search" 
                            placeholder="Buscar por nombre, email o tienda..."
                            autocomplete="off"
                        >
                        <button type="button" id="clear-search" class="clear-btn" style="display: none;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="search-stats">
                        <span id="search-results-count">Cargando vendedores...</span>
                    </div>
                </div>
                
                <!-- Lista de Vendedores -->
                <div class="wcfm-classification-list">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th class="vendor-column">
                                    <i class="fas fa-store"></i>
                                    Vendedor
                                </th>
                                <th class="email-column">
                                    <i class="fas fa-envelope"></i>
                                    Email
                                </th>
                                <th class="comercio-column">
                                    <i class="fas fa-shopping-bag"></i>
                                    Comercio
                                </th>
                                <th class="comercial-column">
                                    <i class="fas fa-handshake"></i>
                                    Comercial
                                </th>
                                <th class="actions-column">
                                    <i class="fas fa-cog"></i>
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody id="vendors-list">
                            <tr>
                                <td colspan="5" class="loading-row">
                                    <i class="fas fa-spinner fa-spin"></i>
                                    Cargando vendedores...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <!-- Paginación -->
                    <div class="wcfm-classification-pagination" id="classification-pagination" style="display: none;">
                        <!-- Se genera dinámicamente por JS -->
                    </div>
                </div>
                
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX: Buscar vendedores
     */
    public function ajax_search_vendors() {
        error_log('🔍 WCFM Classification AJAX: Búsqueda iniciada');
        
        // Temporalmente deshabilitar nonce check para debug
        // check_ajax_referer('wcfm_vendor_classification_nonce', 'nonce');
        
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = 20;
        
        error_log('🔍 WCFM Classification: Buscando vendors - Search: "' . $search . '" - Página: ' . $page);
        
        global $wpdb;
        
        // Si hay búsqueda, usar query personalizada para incluir store_name
        if (!empty($search)) {
            $search_like = '%' . $wpdb->esc_like($search) . '%';
            
            // Obtener IDs de usuarios que coincidan
            $user_ids_query = "
                SELECT DISTINCT u.ID 
                FROM {$wpdb->users} u
                INNER JOIN {$wpdb->usermeta} um_cap ON u.ID = um_cap.user_id 
                    AND um_cap.meta_key = 'wp_capabilities' 
                    AND um_cap.meta_value LIKE '%wcfm_vendor%'
                LEFT JOIN {$wpdb->usermeta} um_store ON u.ID = um_store.user_id 
                    AND um_store.meta_key = 'store_name'
                WHERE (
                    u.user_login LIKE %s OR 
                    u.user_email LIKE %s OR 
                    u.display_name LIKE %s OR
                    um_store.meta_value LIKE %s
                )
                ORDER BY u.user_registered DESC
            ";
            
            $user_ids = $wpdb->get_col($wpdb->prepare($user_ids_query, $search_like, $search_like, $search_like, $search_like));
            $total = count($user_ids);
            
            error_log('🔍 WCFM Classification: IDs encontrados con búsqueda: ' . count($user_ids));
            
            // Aplicar paginación
            $offset = ($page - 1) * $per_page;
            $user_ids_page = array_slice($user_ids, $offset, $per_page);
            
            // Obtener usuarios por IDs
            if (!empty($user_ids_page)) {
                $args = array(
                    'include' => $user_ids_page,
                    'orderby' => 'include'
                );
                $user_query = new WP_User_Query($args);
                $vendors = $user_query->get_results();
            } else {
                $vendors = array();
            }
        } else {
            // Sin búsqueda, usar query estándar
            $args = array(
                'role' => 'wcfm_vendor',
                'orderby' => 'registered',
                'order' => 'DESC',
                'number' => $per_page,
                'offset' => ($page - 1) * $per_page
            );
            
            $user_query = new WP_User_Query($args);
            $vendors = $user_query->get_results();
            $total = $user_query->get_total();
        }
        
        error_log('📊 WCFM Classification: Encontrados ' . count($vendors) . ' vendors (Total: ' . $total . ')');
        
        $vendors_data = array();
        
        foreach ($vendors as $vendor) {
            // Obtener clasificaciones actuales
            $is_comercio = get_user_option('wcfm_is_comercio', $vendor->ID);
            $is_comercial = get_user_option('wcfm_is_comercial', $vendor->ID);
            
            // Por defecto, ambos son true si no están definidos
            if ($is_comercio === false) {
                $is_comercio = true;
            }
            if ($is_comercial === false) {
                $is_comercial = true;
            }
            
            // Obtener store_name
            $store_name = get_user_meta($vendor->ID, 'store_name', true);
            
            // Nombre completo para mostrar
            $full_name = $store_name ? $store_name : $vendor->display_name;
            if ($store_name && $store_name !== $vendor->display_name) {
                $full_name .= ' (' . $vendor->display_name . ')';
            }
            
            $vendors_data[] = array(
                'id' => $vendor->ID,
                'user_login' => $vendor->user_login,
                'display_name' => $vendor->display_name,
                'store_name' => $store_name,
                'full_name' => $full_name,
                'email' => $vendor->user_email,
                'is_comercio' => (bool) $is_comercio,
                'is_comercial' => (bool) $is_comercial,
                'registered' => $vendor->user_registered
            );
        }
        
        wp_send_json_success(array(
            'vendors' => $vendors_data,
            'total' => $total,
            'pages' => ceil($total / $per_page),
            'current_page' => $page,
            'per_page' => $per_page
        ));
    }
    
    /**
     * AJAX: Actualizar clasificación de vendedor
     */
    public function ajax_update_classification() {
        error_log('💾 WCFM Classification AJAX: Actualización iniciada');
        
        // Temporalmente deshabilitar nonce check para debug
        // check_ajax_referer('wcfm_vendor_classification_nonce', 'nonce');
        
        $vendor_id = isset($_POST['vendor_id']) ? intval($_POST['vendor_id']) : 0;
        
        // Convertir correctamente string "true"/"false" a booleano
        $is_comercio = isset($_POST['is_comercio']) && ($_POST['is_comercio'] === 'true' || $_POST['is_comercio'] === true || $_POST['is_comercio'] === 1 || $_POST['is_comercio'] === '1');
        $is_comercial = isset($_POST['is_comercial']) && ($_POST['is_comercial'] === 'true' || $_POST['is_comercial'] === true || $_POST['is_comercial'] === 1 || $_POST['is_comercial'] === '1');
        
        error_log('📥 WCFM Classification: POST recibido - vendor_id=' . $vendor_id . ', is_comercio=' . ($_POST['is_comercio'] ?? 'undefined') . ', is_comercial=' . ($_POST['is_comercial'] ?? 'undefined'));
        error_log('📥 WCFM Classification: Convertido - is_comercio=' . ($is_comercio ? 'true' : 'false') . ', is_comercial=' . ($is_comercial ? 'true' : 'false'));
        
        if (!$vendor_id) {
            wp_send_json_error(array(
                'message' => 'ID de vendedor no válido'
            ));
        }
        
        // Verificar que el usuario existe y es vendor
        $user = get_user_by('ID', $vendor_id);
        if (!$user || !in_array('wcfm_vendor', $user->roles)) {
            wp_send_json_error(array(
                'message' => 'El usuario no es un vendedor válido'
            ));
        }
        
        error_log('💾 WCFM Classification: Actualizando vendor #' . $vendor_id . ' - Comercio: ' . ($is_comercio ? 'Sí' : 'No') . ' - Comercial: ' . ($is_comercial ? 'Sí' : 'No'));
        
        // Guardar clasificaciones en user_option
        update_user_option($vendor_id, 'wcfm_is_comercio', $is_comercio);
        update_user_option($vendor_id, 'wcfm_is_comercial', $is_comercial);
        
        // Verificar que se guardó correctamente
        $saved_comercio = get_user_option('wcfm_is_comercio', $vendor_id);
        $saved_comercial = get_user_option('wcfm_is_comercial', $vendor_id);
        
        error_log('✅ WCFM Classification: Guardado - Comercio: ' . ($saved_comercio ? 'Sí' : 'No') . ' - Comercial: ' . ($saved_comercial ? 'Sí' : 'No'));
        
        wp_send_json_success(array(
            'message' => 'Clasificación actualizada correctamente',
            'vendor_id' => $vendor_id,
            'is_comercio' => (bool) $saved_comercio,
            'is_comercial' => (bool) $saved_comercial
        ));
    }
}

// Inicializar
new WCFM_Affiliate_Vendor_Classification();

