<?php
/**
 * Clasificación de Clientes
 * 
 * Permite clasificar a los clientes con checkboxes: Revisado, Contrato, Interesa, En Espera, No interesa
 * 
 * @package WCFM_Product_Affiliate
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

error_log('📦 WCFM Customer Classification: Archivo cargado');

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
        add_action('wp_ajax_wcfm_search_customers_classification', array($this, 'ajax_search_customers'));
        add_action('wp_ajax_wcfm_update_customer_classification', array($this, 'ajax_update_classification'));
        add_action('wp_ajax_wcfm_update_customer_code', array($this, 'ajax_update_customer_code'));
        add_action('wp_ajax_wcfm_update_customer_cv_classification', array($this, 'ajax_update_customer_cv_classification'));
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
                Clasifica a tus clientes con los siguientes estados: Revisado, Contrato, Interesa, En Espera, No interesa. Todos los checkboxes están desactivados por defecto.
            </p>
            
            <div class="wcfm-classification-container">
                
                <!-- Overlay de carga -->
                <div id="classification-loading-overlay" class="classification-loading-overlay" style="display: none;">
                    <div class="classification-loading-content">
                        <div class="classification-spinner"></div>
                        <p class="classification-loading-text">Cargando filtros...</p>
                    </div>
                </div>
                
                <!-- Buscador -->
                <div class="wcfm-classification-search">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input 
                            type="text" 
                            id="customer-search" 
                            placeholder="Buscar por nombre, email, teléfono o código CV..."
                            autocomplete="off"
                        >
                        <button type="button" id="clear-search" class="clear-btn" style="display: none;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <!-- Filtros por checkboxes y orden -->
                    <div class="classification-filters" style="margin-top: 15px; padding: 15px; background: #f6f7f7; border-radius: 4px;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 20px;">
                            <div style="flex: 1; min-width: 300px;">
                                <strong style="display: block; margin-bottom: 10px;">
                                    <i class="fas fa-filter"></i> Filtrar por clasificación:
                                </strong>
                                <div style="display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 15px;">
                                    <label style="display: flex; align-items: center; cursor: pointer;">
                                        <input type="checkbox" class="filter-checkbox" value="revisado" style="margin-right: 5px;">
                                        Revisado
                                    </label>
                                    <label style="display: flex; align-items: center; cursor: pointer;">
                                        <input type="checkbox" class="filter-checkbox" value="contrato" style="margin-right: 5px;">
                                        Contrato
                                    </label>
                                    <label style="display: flex; align-items: center; cursor: pointer;">
                                        <input type="checkbox" class="filter-checkbox" value="interesa" style="margin-right: 5px;">
                                        Interesa
                                    </label>
                                    <label style="display: flex; align-items: center; cursor: pointer;">
                                        <input type="checkbox" class="filter-checkbox" value="en_espera" style="margin-right: 5px;">
                                        En Espera
                                    </label>
                                    <label style="display: flex; align-items: center; cursor: pointer;">
                                        <input type="checkbox" class="filter-checkbox" value="no_interesa" style="margin-right: 5px;">
                                        No interesa
                                    </label>
                                    <label style="display: flex; align-items: center; cursor: pointer;">
                                        <input type="checkbox" class="filter-checkbox" value="comercial" style="margin-right: 5px;">
                                        Comercial
                                    </label>
                                    <label style="display: flex; align-items: center; cursor: pointer;">
                                        <input type="checkbox" class="filter-checkbox" value="comercio" style="margin-right: 5px;">
                                        Vendedor
                                    </label>
                                </div>
                                <div>
                                    <strong style="display: block; margin-bottom: 8px; font-size: 12px;">
                                        <i class="fas fa-link"></i> Lógica de filtros:
                                    </strong>
                                    <label style="display: flex; align-items: center; cursor: pointer; margin-right: 15px;">
                                        <input type="radio" name="filter-logic" value="AND" checked style="margin-right: 5px;">
                                        Cumplir TODAS las condiciones (AND)
                                    </label>
                                    <label style="display: flex; align-items: center; cursor: pointer;">
                                        <input type="radio" name="filter-logic" value="OR" style="margin-right: 5px;">
                                        Cumplir AL MENOS UNA condición (OR)
                                    </label>
                                </div>
                            </div>
                            <div style="min-width: 200px;">
                                <strong style="display: block; margin-bottom: 10px;">
                                    <i class="fas fa-sort"></i> Ordenar por:
                                </strong>
                                <select id="order-by" class="order-select" style="width: 100%; padding: 8px; border: 1px solid #8c8f94; border-radius: 4px; font-size: 13px;">
                                    <option value="registered_desc">Fecha de creación (Más recientes)</option>
                                    <option value="registered_asc">Fecha de creación (Más antiguos)</option>
                                    <option value="name_asc">Nombre (A-Z)</option>
                                    <option value="name_desc">Nombre (Z-A)</option>
                                    <option value="email_asc">Email (A-Z)</option>
                                    <option value="email_desc">Email (Z-A)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="search-stats">
                        <span id="search-results-count">Cargando clientes...</span>
                    </div>
                </div>
                
                <!-- Lista de Clientes -->
                <div class="wcfm-classification-list">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th class="customer-column">
                                    <i class="fas fa-user"></i>
                                    Cliente
                                </th>
                                <th class="email-column">
                                    <i class="fas fa-envelope"></i>
                                    Email
                                </th>
                                <th class="phone-column">
                                    <i class="fas fa-phone"></i>
                                    Teléfono
                                </th>
                                <th class="code-column">
                                    <i class="fas fa-key"></i>
                                    Código CV
                                </th>
                                <th class="cv-classification-column">
                                    <i class="fas fa-tag"></i>
                                    Clasificación CV
                                </th>
                                <th class="revisado-column">
                                    <i class="fas fa-check-circle"></i>
                                    Revisado
                                </th>
                                <th class="contrato-column">
                                    <i class="fas fa-file-contract"></i>
                                    Contrato
                                </th>
                                <th class="interesa-column">
                                    <i class="fas fa-heart"></i>
                                    Interesa
                                </th>
                                <th class="en_espera-column">
                                    <i class="fas fa-clock"></i>
                                    En Espera
                                </th>
                                <th class="no_interesa-column">
                                    <i class="fas fa-times-circle"></i>
                                    No interesa
                                </th>
                                <th class="comercio-column">
                                    <i class="fas fa-shopping-bag"></i>
                                    Vendedor
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
                        <tbody id="customers-list">
                            <tr>
                                <td colspan="12" class="loading-row">
                                    <i class="fas fa-spinner fa-spin"></i>
                                    Cargando clientes...
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
     * AJAX: Buscar clientes
     */
    public function ajax_search_customers() {
        error_log('🔍 WCFM Customer Classification AJAX: Búsqueda iniciada');
        
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = 20;
        $order_by = isset($_POST['order_by']) ? sanitize_text_field($_POST['order_by']) : 'registered_desc';
        $filter_logic = isset($_POST['filter_logic']) ? sanitize_text_field($_POST['filter_logic']) : 'AND';
        
        // Filtros por checkboxes
        $filter_revisado = isset($_POST['filter_revisado']) && $_POST['filter_revisado'] === 'true';
        $filter_contrato = isset($_POST['filter_contrato']) && $_POST['filter_contrato'] === 'true';
        $filter_interesa = isset($_POST['filter_interesa']) && $_POST['filter_interesa'] === 'true';
        $filter_en_espera = isset($_POST['filter_en_espera']) && $_POST['filter_en_espera'] === 'true';
        $filter_no_interesa = isset($_POST['filter_no_interesa']) && $_POST['filter_no_interesa'] === 'true';
        $filter_comercial = isset($_POST['filter_comercial']) && $_POST['filter_comercial'] === 'true';
        $filter_comercio = isset($_POST['filter_comercio']) && $_POST['filter_comercio'] === 'true';
        
        error_log('🔍 WCFM Classification: Buscando clientes - Search: "' . $search . '" - Página: ' . $page . ' - Orden: ' . $order_by);
        
        global $wpdb;
        
        // Construir query base para vendedores (wcfm_vendor)
        $vendor_role = array('wcfm_vendor');
        
        // Si hay búsqueda de texto, usar query personalizada para incluir first_name, last_name y teléfono
        if (!empty($search)) {
            global $wpdb;
            
            $search_like = '%' . $wpdb->esc_like($search) . '%';
            
            // Construir condición para rol de vendedor
            $role_where = $wpdb->prepare("um_cap.meta_value LIKE %s", '%"wcfm_vendor"%');
            
            // Construir WHERE para búsqueda (case-insensitive usando LOWER)
            // Convertir el término de búsqueda a minúsculas para comparación
            $search_lower = strtolower($search);
            $search_like_lower = '%' . $wpdb->esc_like($search_lower) . '%';
            
            $search_where = $wpdb->prepare("(
                LOWER(u.user_login) LIKE %s OR 
                LOWER(u.user_email) LIKE %s OR 
                LOWER(u.display_name) LIKE %s OR
                LOWER(COALESCE(um_first.meta_value, '')) LIKE %s OR
                LOWER(COALESCE(um_last.meta_value, '')) LIKE %s OR
                LOWER(COALESCE(um_phone.meta_value, '')) LIKE %s OR
                LOWER(COALESCE(um_code.meta_value, '')) LIKE %s
            )", $search_like_lower, $search_like_lower, $search_like_lower, $search_like_lower, $search_like_lower, $search_like_lower, $search_like_lower);
            
            // Construir WHERE para filtros de checkboxes
            $filter_where = '';
            $filter_conditions = array();
            if ($filter_revisado) {
                $filter_conditions[] = "um_revisado.meta_value = '1'";
            }
            if ($filter_contrato) {
                $filter_conditions[] = "um_contrato.meta_value = '1'";
            }
            if ($filter_interesa) {
                $filter_conditions[] = "um_interesa.meta_value = '1'";
            }
            if ($filter_en_espera) {
                $filter_conditions[] = "um_en_espera.meta_value = '1'";
            }
            if ($filter_no_interesa) {
                $filter_conditions[] = "um_no_interesa.meta_value = '1'";
            }
            if ($filter_comercial) {
                // Comercial: incluir los que tienen '1' o NULL (por defecto es true si no existe)
                $filter_conditions[] = "(um_comercial.meta_value = '1' OR um_comercial.meta_value IS NULL OR um_comercial.meta_value = '')";
            }
            if ($filter_comercio) {
                // Comercio: incluir los que tienen '1' o NULL (por defecto es true si no existe)
                $filter_conditions[] = "(um_comercio.meta_value = '1' OR um_comercio.meta_value IS NULL OR um_comercio.meta_value = '')";
            }
            
            if (!empty($filter_conditions)) {
                // Usar AND u OR según la selección del usuario
                $filter_operator = ($filter_logic === 'OR') ? ' OR ' : ' AND ';
                $filter_where = ' AND (' . implode($filter_operator, $filter_conditions) . ')';
            }
            
            // Construir ORDER BY según el criterio seleccionado
            $order_clause = self::build_order_clause($order_by);
            
            // Query para obtener IDs
            $user_ids_query = "
                SELECT DISTINCT u.ID 
                FROM {$wpdb->users} u
                INNER JOIN {$wpdb->usermeta} um_cap ON u.ID = um_cap.user_id 
                    AND um_cap.meta_key = 'wp_capabilities'
                    AND {$role_where}
                LEFT JOIN {$wpdb->usermeta} um_first ON u.ID = um_first.user_id 
                    AND um_first.meta_key = 'first_name'
                LEFT JOIN {$wpdb->usermeta} um_last ON u.ID = um_last.user_id 
                    AND um_last.meta_key = 'last_name'
                LEFT JOIN {$wpdb->usermeta} um_phone ON u.ID = um_phone.user_id 
                    AND um_phone.meta_key = 'billing_phone'
                LEFT JOIN {$wpdb->usermeta} um_code ON u.ID = um_code.user_id 
                    AND um_code.meta_key = 'codigo-ciudad-virtual'
                LEFT JOIN {$wpdb->usermeta} um_revisado ON u.ID = um_revisado.user_id 
                    AND um_revisado.meta_key = 'customer_revisado'
                LEFT JOIN {$wpdb->usermeta} um_contrato ON u.ID = um_contrato.user_id 
                    AND um_contrato.meta_key = 'customer_contrato'
                LEFT JOIN {$wpdb->usermeta} um_interesa ON u.ID = um_interesa.user_id 
                    AND um_interesa.meta_key = 'customer_interesa'
                LEFT JOIN {$wpdb->usermeta} um_en_espera ON u.ID = um_en_espera.user_id 
                    AND um_en_espera.meta_key = 'customer_en_espera'
                LEFT JOIN {$wpdb->usermeta} um_no_interesa ON u.ID = um_no_interesa.user_id 
                    AND um_no_interesa.meta_key = 'customer_no_interesa'
                LEFT JOIN {$wpdb->usermeta} um_comercial ON u.ID = um_comercial.user_id 
                    AND um_comercial.meta_key = 'wcfm_is_comercial'
                LEFT JOIN {$wpdb->usermeta} um_comercio ON u.ID = um_comercio.user_id 
                    AND um_comercio.meta_key = 'wcfm_is_comercio'
                WHERE {$search_where}
                {$filter_where}
                {$order_clause}
            ";
            
            // Query para obtener total (sin LIMIT)
            $total_query = "
                SELECT COUNT(DISTINCT u.ID) 
                FROM {$wpdb->users} u
                INNER JOIN {$wpdb->usermeta} um_cap ON u.ID = um_cap.user_id 
                    AND um_cap.meta_key = 'wp_capabilities'
                    AND {$role_where}
                LEFT JOIN {$wpdb->usermeta} um_first ON u.ID = um_first.user_id 
                    AND um_first.meta_key = 'first_name'
                LEFT JOIN {$wpdb->usermeta} um_last ON u.ID = um_last.user_id 
                    AND um_last.meta_key = 'last_name'
                LEFT JOIN {$wpdb->usermeta} um_phone ON u.ID = um_phone.user_id 
                    AND um_phone.meta_key = 'billing_phone'
                LEFT JOIN {$wpdb->usermeta} um_code ON u.ID = um_code.user_id 
                    AND um_code.meta_key = 'codigo-ciudad-virtual'
                LEFT JOIN {$wpdb->usermeta} um_revisado ON u.ID = um_revisado.user_id 
                    AND um_revisado.meta_key = 'customer_revisado'
                LEFT JOIN {$wpdb->usermeta} um_contrato ON u.ID = um_contrato.user_id 
                    AND um_contrato.meta_key = 'customer_contrato'
                LEFT JOIN {$wpdb->usermeta} um_interesa ON u.ID = um_interesa.user_id 
                    AND um_interesa.meta_key = 'customer_interesa'
                LEFT JOIN {$wpdb->usermeta} um_en_espera ON u.ID = um_en_espera.user_id 
                    AND um_en_espera.meta_key = 'customer_en_espera'
                LEFT JOIN {$wpdb->usermeta} um_no_interesa ON u.ID = um_no_interesa.user_id 
                    AND um_no_interesa.meta_key = 'customer_no_interesa'
                LEFT JOIN {$wpdb->usermeta} um_comercial ON u.ID = um_comercial.user_id 
                    AND um_comercial.meta_key = 'wcfm_is_comercial'
                LEFT JOIN {$wpdb->usermeta} um_comercio ON u.ID = um_comercio.user_id 
                    AND um_comercio.meta_key = 'wcfm_is_comercio'
                WHERE {$search_where}
                {$filter_where}
            ";
            
            $total = $wpdb->get_var($total_query);
            
            // Aplicar paginación a la query de IDs
            $offset = ($page - 1) * $per_page;
            $user_ids_query .= $wpdb->prepare(" LIMIT %d, %d", $offset, $per_page);
            
            $user_ids = $wpdb->get_col($user_ids_query);
            
            // Obtener usuarios por IDs
            if (!empty($user_ids)) {
                $args = array(
                    'include' => $user_ids,
                    'role' => 'wcfm_vendor',
                    'orderby' => 'include'
                );
                $user_query = new WP_User_Query($args);
                $customers = $user_query->get_results();
            } else {
                $customers = array();
            }
            
            error_log('🔍 WCFM Classification: Búsqueda personalizada - Encontrados ' . count($customers) . ' clientes (Total: ' . $total . ')');
        } else {
            // Sin búsqueda de texto, usar WP_User_Query normal
            // Determinar orderby y order según el criterio seleccionado
            $orderby = 'registered';
            $order = 'DESC';
            
            switch ($order_by) {
                case 'registered_asc':
                    $orderby = 'registered';
                    $order = 'ASC';
                    break;
                case 'name_asc':
                    $orderby = 'display_name';
                    $order = 'ASC';
                    break;
                case 'name_desc':
                    $orderby = 'display_name';
                    $order = 'DESC';
                    break;
                case 'email_asc':
                    $orderby = 'user_email';
                    $order = 'ASC';
                    break;
                case 'email_desc':
                    $orderby = 'user_email';
                    $order = 'DESC';
                    break;
                default: // registered_desc
                    $orderby = 'registered';
                    $order = 'DESC';
                    break;
            }
            
            $args = array(
                'role' => 'wcfm_vendor',
                'orderby' => $orderby,
                'order' => $order,
                'number' => $per_page,
                'offset' => ($page - 1) * $per_page
            );
            
            // Construir meta_query solo para filtros de checkboxes
            $meta_queries = array();
            
            if ($filter_revisado) {
                $meta_queries[] = array(
                    'key' => 'customer_revisado',
                    'value' => '1',
                    'compare' => '='
                );
            }
            if ($filter_contrato) {
                $meta_queries[] = array(
                    'key' => 'customer_contrato',
                    'value' => '1',
                    'compare' => '='
                );
            }
            if ($filter_interesa) {
                $meta_queries[] = array(
                    'key' => 'customer_interesa',
                    'value' => '1',
                    'compare' => '='
                );
            }
            if ($filter_en_espera) {
                $meta_queries[] = array(
                    'key' => 'customer_en_espera',
                    'value' => '1',
                    'compare' => '='
                );
            }
            if ($filter_no_interesa) {
                $meta_queries[] = array(
                    'key' => 'customer_no_interesa',
                    'value' => '1',
                    'compare' => '='
                );
            }
            if ($filter_comercial) {
                // Para comercial, incluir también los que no tienen el meta (NULL) ya que por defecto es true
                // Usar una relación OR para incluir '1' o NULL/vacío
                $meta_queries[] = array(
                    'relation' => 'OR',
                    array(
                        'key' => 'wcfm_is_comercial',
                        'value' => '1',
                        'compare' => '='
                    ),
                    array(
                        'key' => 'wcfm_is_comercial',
                        'compare' => 'NOT EXISTS'
                    ),
                    array(
                        'key' => 'wcfm_is_comercial',
                        'value' => '',
                        'compare' => '='
                    )
                );
            }
            if ($filter_comercio) {
                // Para comercio, incluir también los que no tienen el meta (NULL) ya que por defecto es true
                // Usar una relación OR para incluir '1' o NULL/vacío
                $meta_queries[] = array(
                    'relation' => 'OR',
                    array(
                        'key' => 'wcfm_is_comercio',
                        'value' => '1',
                        'compare' => '='
                    ),
                    array(
                        'key' => 'wcfm_is_comercio',
                        'compare' => 'NOT EXISTS'
                    ),
                    array(
                        'key' => 'wcfm_is_comercio',
                        'value' => '',
                        'compare' => '='
                    )
                );
            }
            
            // Si hay filtros, usar AND u OR según la selección
            if (!empty($meta_queries)) {
                if (count($meta_queries) > 1) {
                    $meta_queries['relation'] = ($filter_logic === 'OR') ? 'OR' : 'AND';
                }
                $args['meta_query'] = $meta_queries;
            }
            
            
            // Ejecutar query
            $user_query = new WP_User_Query($args);
            $customers = $user_query->get_results();
            $total = $user_query->get_total();
            
            error_log('🔍 WCFM Classification: Query normal - Encontrados ' . count($customers) . ' clientes (Total: ' . $total . ')');
        }
        
        $customers_data = array();
        
        foreach ($customers as $customer) {
            // Obtener clasificaciones actuales (por defecto todos desactivados)
            $revisado = get_user_meta($customer->ID, 'customer_revisado', true) === '1';
            $contrato = get_user_meta($customer->ID, 'customer_contrato', true) === '1';
            $interesa = get_user_meta($customer->ID, 'customer_interesa', true) === '1';
            $en_espera = get_user_meta($customer->ID, 'customer_en_espera', true) === '1';
            $no_interesa = get_user_meta($customer->ID, 'customer_no_interesa', true) === '1';
            
            // Obtener comercio y comercial (por defecto true si no están definidos)
            $comercio_raw = get_user_meta($customer->ID, 'wcfm_is_comercio', true);
            $comercial_raw = get_user_meta($customer->ID, 'wcfm_is_comercial', true);
            $comercio = ($comercio_raw === '' || $comercio_raw === '1');
            $comercial = ($comercial_raw === '' || $comercial_raw === '1');
            
            // Obtener teléfono
            $phone = get_user_meta($customer->ID, 'billing_phone', true);
            
            // Nombre completo
            $first_name = get_user_meta($customer->ID, 'first_name', true);
            $last_name = get_user_meta($customer->ID, 'last_name', true);
            $full_name = trim($first_name . ' ' . $last_name);
            if (empty($full_name)) {
                $full_name = $customer->display_name;
            }
            
            $code = get_user_meta($customer->ID, 'codigo-ciudad-virtual', true);
            if ('' === $code) {
                $code = '—';
            }
            
            // Obtener Clasificación CV
            $cv_classification = get_user_meta($customer->ID, 'clasificacion-cv', true);
            if ('' === $cv_classification) {
                $cv_classification = '—';
            }
            
            // Obtener URL del Store Manager
            $store_manager_url = '';
            if (function_exists('get_wcfm_vendors_manage_url')) {
                $store_manager_url = get_wcfm_vendors_manage_url($customer->ID);
            } elseif (function_exists('get_wcfm_url')) {
                $store_manager_url = get_wcfm_url();
            }
            
            $customers_data[] = array(
                'id' => $customer->ID,
                'user_login' => $customer->user_login,
                'display_name' => $customer->display_name,
                'full_name' => $full_name,
                'email' => $customer->user_email,
                'phone' => $phone ? $phone : '',
                'code' => $code,
                'cv_classification' => $cv_classification,
                'revisado' => (bool) $revisado,
                'contrato' => (bool) $contrato,
                'interesa' => (bool) $interesa,
                'en_espera' => (bool) $en_espera,
                'no_interesa' => (bool) $no_interesa,
                'comercio' => (bool) $comercio,
                'comercial' => (bool) $comercial,
                'registered' => $customer->user_registered,
                'store_manager_url' => $store_manager_url
            );
        }
        
        wp_send_json_success(array(
            'customers' => $customers_data,
            'total' => $total,
            'pages' => ceil($total / $per_page),
            'current_page' => $page,
            'per_page' => $per_page
        ));
    }
    
    /**
     * Construir cláusula ORDER BY para query SQL personalizada
     * 
     * @param string $order_by Criterio de ordenación
     * @return string Cláusula ORDER BY
     */
    private static function build_order_clause($order_by) {
        global $wpdb;
        
        switch ($order_by) {
            case 'registered_asc':
                return 'ORDER BY u.user_registered ASC';
            case 'name_asc':
                return 'ORDER BY LOWER(u.display_name) ASC, LOWER(COALESCE(um_first.meta_value, \'\')) ASC, LOWER(COALESCE(um_last.meta_value, \'\')) ASC';
            case 'name_desc':
                return 'ORDER BY LOWER(u.display_name) DESC, LOWER(COALESCE(um_first.meta_value, \'\')) DESC, LOWER(COALESCE(um_last.meta_value, \'\')) DESC';
            case 'email_asc':
                return 'ORDER BY LOWER(u.user_email) ASC';
            case 'email_desc':
                return 'ORDER BY LOWER(u.user_email) DESC';
            default: // registered_desc
                return 'ORDER BY u.user_registered DESC';
        }
    }
    
    /**
     * AJAX: Actualizar clasificación de cliente
     */
    public function ajax_update_classification() {
        error_log('💾 WCFM Customer Classification AJAX: Actualización iniciada');
        
        $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
        
        // Convertir checkboxes a booleanos
        $revisado = isset($_POST['revisado']) && ($_POST['revisado'] === 'true' || $_POST['revisado'] === true || $_POST['revisado'] === 1 || $_POST['revisado'] === '1');
        $contrato = isset($_POST['contrato']) && ($_POST['contrato'] === 'true' || $_POST['contrato'] === true || $_POST['contrato'] === 1 || $_POST['contrato'] === '1');
        $interesa = isset($_POST['interesa']) && ($_POST['interesa'] === 'true' || $_POST['interesa'] === true || $_POST['interesa'] === 1 || $_POST['interesa'] === '1');
        $en_espera = isset($_POST['en_espera']) && ($_POST['en_espera'] === 'true' || $_POST['en_espera'] === true || $_POST['en_espera'] === 1 || $_POST['en_espera'] === '1');
        $no_interesa = isset($_POST['no_interesa']) && ($_POST['no_interesa'] === 'true' || $_POST['no_interesa'] === true || $_POST['no_interesa'] === 1 || $_POST['no_interesa'] === '1');
        $comercio = isset($_POST['comercio']) && ($_POST['comercio'] === 'true' || $_POST['comercio'] === true || $_POST['comercio'] === 1 || $_POST['comercio'] === '1');
        $comercial = isset($_POST['comercial']) && ($_POST['comercial'] === 'true' || $_POST['comercial'] === true || $_POST['comercial'] === 1 || $_POST['comercial'] === '1');
        
        error_log('📥 WCFM Classification: POST recibido - customer_id=' . $customer_id);
        
        if (!$customer_id) {
            wp_send_json_error(array(
                'message' => 'ID de cliente no válido'
            ));
        }
        
        // Verificar que el usuario existe y es cliente
        $user = get_user_by('ID', $customer_id);
        if (!$user) {
            wp_send_json_error(array(
                'message' => 'El usuario no existe'
            ));
        }
        
        error_log('💾 WCFM Classification: Actualizando cliente #' . $customer_id);
        
        // Guardar clasificaciones en user_meta como string '1' o '0'
        update_user_meta($customer_id, 'customer_revisado', $revisado ? '1' : '0');
        update_user_meta($customer_id, 'customer_contrato', $contrato ? '1' : '0');
        update_user_meta($customer_id, 'customer_interesa', $interesa ? '1' : '0');
        update_user_meta($customer_id, 'customer_en_espera', $en_espera ? '1' : '0');
        update_user_meta($customer_id, 'customer_no_interesa', $no_interesa ? '1' : '0');
        update_user_meta($customer_id, 'wcfm_is_comercio', $comercio ? '1' : '0');
        update_user_meta($customer_id, 'wcfm_is_comercial', $comercial ? '1' : '0');
        
        wp_send_json_success(array(
            'message' => 'Clasificación actualizada correctamente',
            'customer_id' => $customer_id,
            'revisado' => $revisado,
            'contrato' => $contrato,
            'interesa' => $interesa,
            'en_espera' => $en_espera,
            'no_interesa' => $no_interesa,
            'comercio' => $comercio,
            'comercial' => $comercial
        ));
    }
    
    /**
     * AJAX: Actualizar código ciudad virtual
     */
    public function ajax_update_customer_code() {
        $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
        $code = isset($_POST['code']) ? sanitize_text_field(wp_unslash($_POST['code'])) : '';
        
        if (!$customer_id) {
            wp_send_json_error(array('message' => 'ID de cliente no válido'));
        }
        
        $user = get_user_by('ID', $customer_id);
        if (!$user) {
            wp_send_json_error(array('message' => 'El usuario no existe'));
        }
        
        update_user_meta($customer_id, 'codigo-ciudad-virtual', $code);
        
        wp_send_json_success(array(
            'message' => 'Código actualizado correctamente',
            'code' => $code !== '' ? $code : '—'
        ));
    }
    
    /**
     * AJAX: Actualizar clasificación CV
     */
    public function ajax_update_customer_cv_classification() {
        $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
        $cv_classification = isset($_POST['cv_classification']) ? sanitize_text_field(wp_unslash($_POST['cv_classification'])) : '';
        
        if (!$customer_id) {
            wp_send_json_error(array('message' => 'ID de cliente no válido'));
        }
        
        $user = get_user_by('ID', $customer_id);
        if (!$user) {
            wp_send_json_error(array('message' => 'El usuario no existe'));
        }
        
        update_user_meta($customer_id, 'clasificacion-cv', $cv_classification);
        
        wp_send_json_success(array(
            'message' => 'Clasificación CV actualizada correctamente',
            'cv_classification' => $cv_classification !== '' ? $cv_classification : '—'
        ));
    }
    
}

// Inicializar
new WCFM_Affiliate_Vendor_Classification();

