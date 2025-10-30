<?php
/**
 * Bulk Affiliate Manager - Gestión masiva de afiliación de productos
 *
 * @package WCFM_Product_Affiliate
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCFM_Affiliate_Bulk_Manager {
    
    /**
     * Constructor
     */
    public function __construct() {
        error_log('WCFM_Affiliate_Bulk_Manager: Constructor called');
        
        // Registrar hooks AJAX inmediatamente
        add_action('wp_ajax_wcfm_affiliate_search_products', array($this, 'ajax_search_products'), 1);
        add_action('wp_ajax_wcfm_affiliate_add_to_pool', array($this, 'ajax_add_to_pool'), 1);
        add_action('wp_ajax_wcfm_affiliate_remove_from_pool', array($this, 'ajax_remove_from_pool'), 1);
        add_action('wp_ajax_wcfm_affiliate_search_vendors', array($this, 'ajax_search_vendors'), 1);
        add_action('wp_ajax_wcfm_affiliate_bulk_affiliate', array($this, 'ajax_bulk_affiliate'), 1);
        
        error_log('WCFM_Affiliate_Bulk_Manager: AJAX hooks registered');
        
        // Otros hooks
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'), 20);
        
        // Enqueue scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Menú principal
        add_menu_page(
            __('Productos Afiliados', 'wcfm-product-affiliate'),
            __('Productos Afiliados', 'wcfm-product-affiliate'),
            'manage_woocommerce',
            'wcfm-affiliate-bulk',
            array($this, 'render_page'),
            'dashicons-networking',
            56
        );
        
        // Submenú "Gestión Masiva"
        add_submenu_page(
            'wcfm-affiliate-bulk',
            __('Gestión Masiva', 'wcfm-product-affiliate'),
            __('Gestión Masiva', 'wcfm-product-affiliate'),
            'manage_woocommerce',
            'wcfm-affiliate-bulk',
            array($this, 'render_page')
        );
    }
    
    /**
     * Enqueue scripts
     */
    public function enqueue_scripts($hook) {
        if ('toplevel_page_wcfm-affiliate-bulk' !== $hook) {
            return;
        }
        
        wp_enqueue_style('wcfm-affiliate-bulk', 
            WCFM_AFFILIATE_PLUGIN_URL . 'admin/assets/css/bulk-manager.css', 
            array(), 
            WCFM_AFFILIATE_VERSION
        );
        
        wp_enqueue_script('wcfm-affiliate-bulk', 
            WCFM_AFFILIATE_PLUGIN_URL . 'admin/assets/js/bulk-manager.js', 
            array('jquery'), 
            WCFM_AFFILIATE_VERSION, 
            true
        );
        
        wp_localize_script('wcfm-affiliate-bulk', 'wcfmAffiliateBulk', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wcfm_affiliate_bulk_nonce'),
            'i18n' => array(
                'selectProducts' => __('Por favor selecciona al menos un producto', 'wcfm-product-affiliate'),
                'selectVendor' => __('Por favor selecciona un vendedor', 'wcfm-product-affiliate'),
                'confirmAffiliate' => __('¿Estás seguro de afiliar los productos seleccionados?', 'wcfm-product-affiliate'),
                'success' => __('Productos afiliados correctamente', 'wcfm-product-affiliate'),
                'error' => __('Error al afiliar productos', 'wcfm-product-affiliate'),
            )
        ));
    }
    
    /**
     * Render admin page
     */
    public function render_page() {
        // Get pool products
        $pool = $this->get_pool();
        ?>
        <div class="wrap">
            <h1><?php _e('Gestión Masiva de Productos para Afiliar', 'wcfm-product-affiliate'); ?></h1>
            
            <div class="wcfm-affiliate-bulk-container">
                <!-- Buscador de productos -->
                <div class="wcfm-affiliate-search-section">
                    <h2><?php _e('Buscar Productos', 'wcfm-product-affiliate'); ?></h2>
                    <div class="search-box">
                        <input type="text" id="product-search" placeholder="<?php _e('Buscar productos...', 'wcfm-product-affiliate'); ?>" />
                        <button type="button" id="search-products-btn" class="button button-primary">
                            <?php _e('Buscar', 'wcfm-product-affiliate'); ?>
                        </button>
                    </div>
                    
                    <div id="search-results" style="display:none;">
                        <h3><?php _e('Resultados de búsqueda', 'wcfm-product-affiliate'); ?> <span id="search-results-count"></span></h3>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th class="check-column"><input type="checkbox" id="select-all-search" /></th>
                                    <th><?php _e('Imagen', 'wcfm-product-affiliate'); ?></th>
                                    <th><?php _e('Producto', 'wcfm-product-affiliate'); ?></th>
                                    <th><?php _e('Vendedor', 'wcfm-product-affiliate'); ?></th>
                                    <th><?php _e('Precio', 'wcfm-product-affiliate'); ?></th>
                                    <th><?php _e('Acción', 'wcfm-product-affiliate'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="search-results-body"></tbody>
                        </table>
                        
                        <div class="search-pagination" style="margin-top:15px; text-align:center;"></div>
                        
                        <div style="margin-top:15px;">
                            <button type="button" id="add-selected-search-btn" class="button button-primary">
                                <?php _e('Añadir Seleccionados al Pool', 'wcfm-product-affiliate'); ?>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Tabla de productos para afiliar -->
                <div class="wcfm-affiliate-pool-section">
                    <div class="pool-header">
                        <h2><?php _e('Productos Disponibles para Afiliar', 'wcfm-product-affiliate'); ?></h2>
                        <div class="pool-actions">
                            <button type="button" id="delete-selected-btn" class="button">
                                <?php _e('Borrar Seleccionados', 'wcfm-product-affiliate'); ?>
                            </button>
                            <button type="button" id="send-to-vendor-btn" class="button button-primary">
                                <?php _e('Enviar a Afiliado', 'wcfm-product-affiliate'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <table class="wp-list-table widefat fixed striped" id="products-pool-table">
                        <thead>
                            <tr>
                                <th class="check-column">
                                    <input type="checkbox" id="select-all-products" />
                                </th>
                                <th><?php _e('Imagen', 'wcfm-product-affiliate'); ?></th>
                                <th><?php _e('Producto', 'wcfm-product-affiliate'); ?></th>
                                <th><?php _e('Vendedor Original', 'wcfm-product-affiliate'); ?></th>
                                <th><?php _e('Precio', 'wcfm-product-affiliate'); ?></th>
                                <th><?php _e('Stock', 'wcfm-product-affiliate'); ?></th>
                                <th><?php _e('Acciones', 'wcfm-product-affiliate'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="pool-products-body">
                            <?php if (empty($pool)) : ?>
                                <tr>
                                    <td colspan="7" style="text-align:center;">
                                        <?php _e('No hay productos en la lista. Busca y añade productos usando el buscador de arriba.', 'wcfm-product-affiliate'); ?>
                                    </td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ($pool as $product_id) : 
                                    $this->render_pool_row($product_id);
                                endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Modal para seleccionar vendedor -->
        <div id="vendor-select-modal" class="wcfm-modal" style="display:none;">
            <div class="wcfm-modal-content">
                <div class="wcfm-modal-header">
                    <h3><?php _e('Seleccionar Vendedor Afiliado', 'wcfm-product-affiliate'); ?></h3>
                    <span class="wcfm-modal-close">&times;</span>
                </div>
                
                <div class="wcfm-modal-body">
                    <!-- Filtros de Clasificación -->
                    <div class="vendor-classification-filters" style="margin-bottom: 20px; padding: 15px; background: #f6f7f7; border-radius: 4px;">
                        <h4 style="margin: 0 0 12px 0; font-size: 14px; font-weight: 600; color: #2c3338;">
                            <i class="fas fa-filter"></i> <?php _e('Filtrar por Clasificación:', 'wcfm-product-affiliate'); ?>
                        </h4>
                        <div style="display: flex; gap: 25px; align-items: center;">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 14px;">
                                <input type="checkbox" id="filter-comercio" class="classification-filter" value="comercio" checked style="width: 18px; height: 18px; cursor: pointer;" />
                                <i class="fas fa-shopping-bag" style="color: #2271b1;"></i>
                                <span><?php _e('Comercio', 'wcfm-product-affiliate'); ?></span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 14px;">
                                <input type="checkbox" id="filter-comercial" class="classification-filter" value="comercial" checked style="width: 18px; height: 18px; cursor: pointer;" />
                                <i class="fas fa-handshake" style="color: #2271b1;"></i>
                                <span><?php _e('Comercial', 'wcfm-product-affiliate'); ?></span>
                            </label>
                            <span id="filter-count" style="margin-left: auto; font-size: 13px; color: #646970; font-weight: 500;"></span>
                        </div>
                    </div>
                    
                    <div class="vendor-search-box">
                        <input type="text" id="vendor-search" placeholder="<?php _e('Buscar vendedor...', 'wcfm-product-affiliate'); ?>" />
                        <button type="button" id="search-vendors-btn" class="button">
                            <?php _e('Buscar', 'wcfm-product-affiliate'); ?>
                        </button>
                    </div>
                    
                    <div id="vendors-list-container">
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th class="check-column"><input type="checkbox" id="select-all-vendors" /></th>
                                    <th><?php _e('Vendedor', 'wcfm-product-affiliate'); ?></th>
                                    <th><?php _e('Email', 'wcfm-product-affiliate'); ?></th>
                                    <th><?php _e('Productos', 'wcfm-product-affiliate'); ?></th>
                                    <th><?php _e('Fecha Registro', 'wcfm-product-affiliate'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="vendors-list-body"></tbody>
                        </table>
                        
                        <div class="vendor-pagination"></div>
                    </div>
                </div>
                
                <div class="wcfm-modal-footer">
                    <button type="button" id="cancel-vendor-btn" class="button">
                        <?php _e('Cancelar', 'wcfm-product-affiliate'); ?>
                    </button>
                    <button type="button" id="affiliate-to-selected-vendors-btn" class="button button-primary">
                        <?php _e('Afiliar a Seleccionados', 'wcfm-product-affiliate'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Modal de confirmación -->
        <div id="confirm-affiliate-modal" class="wcfm-modal" style="display:none;">
            <div class="wcfm-modal-content wcfm-modal-small">
                <div class="wcfm-modal-header">
                    <h3><?php _e('Confirmar Afiliación', 'wcfm-product-affiliate'); ?></h3>
                    <span class="wcfm-modal-close">&times;</span>
                </div>
                
                <div class="wcfm-modal-body">
                    <p><?php _e('Vas a afiliar los siguientes productos al vendedor:', 'wcfm-product-affiliate'); ?></p>
                    <p><strong id="selected-vendor-name"></strong></p>
                    
                    <div class="products-to-affiliate">
                        <h4><?php _e('Productos seleccionados:', 'wcfm-product-affiliate'); ?></h4>
                        <ul id="products-to-affiliate-list"></ul>
                    </div>
                    
                    <p class="description">
                        <?php _e('Puedes desmarcar productos que no quieras afiliar:', 'wcfm-product-affiliate'); ?>
                    </p>
                </div>
                
                <div class="wcfm-modal-footer">
                    <button type="button" id="cancel-affiliate-btn" class="button">
                        <?php _e('Cancelar', 'wcfm-product-affiliate'); ?>
                    </button>
                    <button type="button" id="confirm-affiliate-btn" class="button button-primary">
                        <?php _e('Aceptar y Afiliar', 'wcfm-product-affiliate'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render pool product row
     */
    private function render_pool_row($product_id) {
        $product = wc_get_product($product_id);
        if (!$product) {
            return;
        }
        
        $vendor_id = get_post_field('post_author', $product_id);
        $vendor = get_userdata($vendor_id);
        
        // Obtener nombre de la tienda
        $store_name = get_user_meta($vendor_id, 'store_name', true);
        if (empty($store_name)) {
            $store_name = $vendor->display_name;
        }
        
        // Combinar nombre de tienda y usuario
        $vendor_full_name = $store_name;
        if ($store_name !== $vendor->display_name) {
            $vendor_full_name .= ' (' . $vendor->display_name . ')';
        }
        
        ?>
        <tr data-product-id="<?php echo esc_attr($product_id); ?>">
            <td class="check-column">
                <input type="checkbox" class="product-checkbox" value="<?php echo esc_attr($product_id); ?>" checked />
            </td>
            <td><?php echo $product->get_image('thumbnail'); ?></td>
            <td>
                <strong><?php echo esc_html($product->get_name()); ?></strong><br>
                <small>ID: <?php echo $product_id; ?></small>
            </td>
            <td><?php echo esc_html($vendor_full_name); ?></td>
            <td><?php echo $product->get_price_html(); ?></td>
            <td>
                <?php if ($product->is_in_stock()) : ?>
                    <span class="in-stock"><?php _e('En stock', 'wcfm-product-affiliate'); ?></span>
                <?php else : ?>
                    <span class="out-of-stock"><?php _e('Sin stock', 'wcfm-product-affiliate'); ?></span>
                <?php endif; ?>
            </td>
            <td>
                <button type="button" class="button remove-product" data-product-id="<?php echo esc_attr($product_id); ?>">
                    <?php _e('Quitar', 'wcfm-product-affiliate'); ?>
                </button>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Get pool products
     */
    private function get_pool() {
        return get_option('wcfm_affiliate_product_pool', array());
    }
    
    /**
     * Update pool products
     */
    private function update_pool($pool) {
        update_option('wcfm_affiliate_product_pool', $pool);
    }
    
    /**
     * AJAX: Search products
     */
    public function ajax_search_products() {
        // FORZAR respuesta para test
        error_log('============================================');
        error_log('WCFM Affiliate: ajax_search_products CALLED!!!');
        error_log('POST data: ' . print_r($_POST, true));
        error_log('============================================');
        
        // TEMPORALMENTE DESACTIVADO PARA DEBUG
        // Verificar nonce (false = no morir, solo retornar false)
        //$nonce_check = check_ajax_referer('wcfm_affiliate_bulk_nonce', 'nonce', false);
        //error_log('WCFM Affiliate: Nonce check result: ' . ($nonce_check ? 'true' : 'false'));
        
        //if (!$nonce_check) {
        //    error_log('WCFM Affiliate: Nonce verification failed');
        //    wp_send_json_error(array('message' => __('Nonce inválido', 'wcfm-product-affiliate')));
        //    return;
        //}
        
        // Verificar permisos
        if (!current_user_can('manage_woocommerce')) {
            error_log('WCFM Affiliate: Permission check failed');
            wp_send_json_error(array('message' => __('Sin permisos', 'wcfm-product-affiliate')));
            return;
        }
        
        error_log('WCFM Affiliate: All checks passed, proceeding with search');
        
        try {
            $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
            $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
            $per_page = 20;
            
            $args = array(
                'post_type' => 'product',
                'post_status' => 'publish',
                'posts_per_page' => $per_page,
                'paged' => $page,
                's' => $search,
                'orderby' => 'date',
                'order' => 'DESC',
                'meta_query' => array(
                    'relation' => 'OR',
                    array(
                        'key' => '_wcfm_affiliate_original_product',
                        'compare' => 'NOT EXISTS',
                    ),
                    array(
                        'key' => '_wcfm_affiliate_original_product',
                        'value' => '',
                        'compare' => '=',
                    ),
                ),
            );
            
            $query = new WP_Query($args);
            $results = array();
            
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $product = wc_get_product(get_the_ID());
                    if (!$product) {
                        continue;
                    }
                    
                    $vendor_id = get_post_field('post_author', get_the_ID());
                    $vendor = get_userdata($vendor_id);
                    
                    // Obtener nombre de la tienda
                    $store_name = get_user_meta($vendor_id, 'store_name', true);
                    if (empty($store_name)) {
                        $store_name = $vendor ? $vendor->display_name : 'Desconocido';
                    }
                    
                    // Combinar nombre de tienda y usuario
                    $vendor_full_name = $store_name;
                    if ($vendor && $store_name !== $vendor->display_name) {
                        $vendor_full_name .= ' (' . $vendor->display_name . ')';
                    }
                    
                    $results[] = array(
                        'id' => get_the_ID(),
                        'name' => $product->get_name(),
                        'vendor' => $vendor_full_name,
                        'price' => $product->get_price_html(),
                        'image' => $product->get_image('thumbnail'),
                    );
                }
                wp_reset_postdata();
            }
            
            wp_send_json_success(array(
                'products' => $results,
                'total' => $query->found_posts,
                'pages' => $query->max_num_pages,
                'current_page' => $page,
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Error: ' . $e->getMessage()));
        }
    }
    
    /**
     * AJAX: Add to pool
     */
    public function ajax_add_to_pool() {
        // TEMPORALMENTE DESACTIVADO PARA DEBUG
        //check_ajax_referer('wcfm_affiliate_bulk_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Sin permisos', 'wcfm-product-affiliate')));
            return;
        }
        
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        
        if (!$product_id) {
            wp_send_json_error(array('message' => __('ID de producto inválido', 'wcfm-product-affiliate')));
        }
        
        $pool = $this->get_pool();
        
        if (in_array($product_id, $pool)) {
            wp_send_json_error(array('message' => __('El producto ya está en la lista', 'wcfm-product-affiliate')));
        }
        
        $pool[] = $product_id;
        $this->update_pool($pool);
        
        wp_send_json_success(array('message' => __('Producto añadido a la lista', 'wcfm-product-affiliate')));
    }
    
    /**
     * AJAX: Remove from pool
     */
    public function ajax_remove_from_pool() {
        // TEMPORALMENTE DESACTIVADO PARA DEBUG
        //check_ajax_referer('wcfm_affiliate_bulk_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Sin permisos', 'wcfm-product-affiliate')));
            return;
        }
        
        $product_ids = isset($_POST['product_ids']) ? array_map('intval', $_POST['product_ids']) : array();
        
        if (empty($product_ids)) {
            wp_send_json_error(array('message' => __('No se especificaron productos', 'wcfm-product-affiliate')));
        }
        
        $pool = $this->get_pool();
        $pool = array_diff($pool, $product_ids);
        $this->update_pool($pool);
        
        wp_send_json_success(array('message' => __('Productos eliminados de la lista', 'wcfm-product-affiliate')));
    }
    
    /**
     * AJAX: Search vendors
     */
    public function ajax_search_vendors() {
        // TEMPORALMENTE DESACTIVADO PARA DEBUG
        //check_ajax_referer('wcfm_affiliate_bulk_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Sin permisos', 'wcfm-product-affiliate')));
            return;
        }
        
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = 10;
        
        // Filtros de clasificación
        $filter_comercio = isset($_POST['filter_comercio']) && $_POST['filter_comercio'] === 'true';
        $filter_comercial = isset($_POST['filter_comercial']) && $_POST['filter_comercial'] === 'true';
        
        error_log('🔍 WCFM Bulk: Búsqueda vendors - Search: "' . $search . '" - Comercio: ' . ($filter_comercio ? 'Sí' : 'No') . ' - Comercial: ' . ($filter_comercial ? 'Sí' : 'No'));
        
        global $wpdb;
        
        // Query personalizada para incluir filtros de clasificación
        $where_conditions = array("um_cap.meta_value LIKE '%wcfm_vendor%'");
        $join_clauses = array();
        $params = array();
        
        // Filtro de búsqueda
        if (!empty($search)) {
            $search_like = '%' . $wpdb->esc_like($search) . '%';
            $where_conditions[] = "(
                u.user_login LIKE %s OR 
                u.user_email LIKE %s OR 
                u.display_name LIKE %s OR
                um_store.meta_value LIKE %s
            )";
            $params[] = $search_like;
            $params[] = $search_like;
            $params[] = $search_like;
            $params[] = $search_like;
            
            $join_clauses[] = "LEFT JOIN {$wpdb->usermeta} um_store ON u.ID = um_store.user_id AND um_store.meta_key = 'store_name'";
        }
        
        // Filtros de clasificación
        // LÓGICA: Checkbox MARCADO = MOSTRAR ese tipo
        //         Checkbox DESMARCADO = OCULTAR ese tipo
        
        $join_clauses[] = "LEFT JOIN {$wpdb->usermeta} um_comercio ON u.ID = um_comercio.user_id AND um_comercio.meta_key = 'wcfm_is_comercio'";
        $join_clauses[] = "LEFT JOIN {$wpdb->usermeta} um_comercial ON u.ID = um_comercial.user_id AND um_comercial.meta_key = 'wcfm_is_comercial'";
        
        $classification_conditions = array();
        
        if ($filter_comercio) {
            // Mostrar comercios: NULL (por defecto) o '1' (explícito)
            $classification_conditions[] = "(um_comercio.meta_value IS NULL OR um_comercio.meta_value = '1')";
        }
        
        if ($filter_comercial) {
            // Mostrar comerciales: NULL (por defecto) o '1' (explícito)
            $classification_conditions[] = "(um_comercial.meta_value IS NULL OR um_comercial.meta_value = '1')";
        }
        
        if (!empty($classification_conditions)) {
            // Unir con OR: mostrar si cumple AL MENOS una condición
            $where_conditions[] = '(' . implode(' OR ', $classification_conditions) . ')';
        }
        
        if (!$filter_comercio && !$filter_comercial) {
            // Ninguno seleccionado = no mostrar nada
            $where_conditions[] = "1 = 0";
        }
        
        // Construir query
        $join_sql = implode(' ', array_unique($join_clauses));
        $where_sql = implode(' AND ', $where_conditions);
        
        $count_query = "
            SELECT COUNT(DISTINCT u.ID)
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->usermeta} um_cap ON u.ID = um_cap.user_id 
                AND um_cap.meta_key = 'wp_capabilities'
            {$join_sql}
            WHERE {$where_sql}
        ";
        
        $total = $wpdb->get_var(!empty($params) ? $wpdb->prepare($count_query, ...$params) : $count_query);
        
        // Query de datos
        $offset = ($page - 1) * $per_page;
        $data_query = "
            SELECT DISTINCT u.ID, u.user_login, u.user_email, u.display_name, u.user_registered
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->usermeta} um_cap ON u.ID = um_cap.user_id 
                AND um_cap.meta_key = 'wp_capabilities'
            {$join_sql}
            WHERE {$where_sql}
            ORDER BY u.user_registered DESC
            LIMIT {$per_page} OFFSET {$offset}
        ";
        
        $vendor_data = $wpdb->get_results(!empty($params) ? $wpdb->prepare($data_query, ...$params) : $data_query);
        
        $vendors = array();
        foreach ($vendor_data as $data) {
            $vendors[] = get_user_by('ID', $data->ID);
        }
        
        $results = array();
        foreach ($vendors as $vendor) {
            $product_count = count_user_posts($vendor->ID, 'product');
            $registered = get_date_from_gmt($vendor->user_registered, 'd/m/Y');
            
            // Obtener nombre de la tienda
            $store_name = get_user_meta($vendor->ID, 'store_name', true);
            if (empty($store_name)) {
                $store_name = $vendor->display_name;
            }
            
            // Combinar nombre de tienda y usuario
            $full_name = $store_name;
            if ($store_name !== $vendor->display_name) {
                $full_name .= ' (' . $vendor->display_name . ')';
            }
            
            $results[] = array(
                'id' => $vendor->ID,
                'name' => $full_name,
                'store_name' => $store_name,
                'user_name' => $vendor->display_name,
                'email' => $vendor->user_email,
                'products' => $product_count,
                'registered' => $registered,
            );
        }
        
        wp_send_json_success(array(
            'vendors' => $results,
            'total' => $total,
            'pages' => ceil($total / $per_page),
            'current_page' => $page,
        ));
    }
    
    /**
     * AJAX: Bulk affiliate
     */
    public function ajax_bulk_affiliate() {
        // Log para debug
        error_log('============================================');
        error_log('WCFM Affiliate: ajax_bulk_affiliate CALLED!!!');
        error_log('POST data: ' . print_r($_POST, true));
        error_log('============================================');
        
        // TEMPORALMENTE DESACTIVADO PARA DEBUG
        //check_ajax_referer('wcfm_affiliate_bulk_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            error_log('WCFM Affiliate: Permission check failed in bulk_affiliate');
            wp_send_json_error(array('message' => __('Sin permisos', 'wcfm-product-affiliate')));
            return;
        }
        
        error_log('WCFM Affiliate: Permissions OK, proceeding with bulk affiliate');
        
        $product_ids = isset($_POST['product_ids']) ? array_map('intval', $_POST['product_ids']) : array();
        $vendor_id = isset($_POST['vendor_id']) ? intval($_POST['vendor_id']) : 0;
        
        if (empty($product_ids)) {
            wp_send_json_error(array('message' => __('No se especificaron productos', 'wcfm-product-affiliate')));
        }
        
        if (!$vendor_id) {
            wp_send_json_error(array('message' => __('No se especificó vendedor', 'wcfm-product-affiliate')));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . WCFM_Affiliate_DB::TABLE_AFFILIATES;
        
        $success_count = 0;
        $errors = array();
        
        foreach ($product_ids as $product_id) {
            // Verificar si el producto existe
            $product = wc_get_product($product_id);
            if (!$product) {
                $errors[] = sprintf(__('Producto ID %d no encontrado', 'wcfm-product-affiliate'), $product_id);
                continue;
            }
            
            // Verificar si ya existe la afiliación
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} WHERE product_id = %d AND vendor_id = %d",
                $product_id,
                $vendor_id
            ));
            
            if ($exists > 0) {
                $errors[] = sprintf(__('El producto "%s" ya está afiliado a este vendedor', 'wcfm-product-affiliate'), $product->get_name());
                continue;
            }
            
            // Obtener el propietario original del producto
            $product_owner_id = get_post_field('post_author', $product_id);
            
            // Crear afiliación
            error_log('Intentando insertar afiliación: product_id=' . $product_id . ', vendor_id=' . $vendor_id . ', owner_id=' . $product_owner_id);
            
            $result = $wpdb->insert(
                $table_name,
                array(
                    'vendor_id' => $vendor_id,
                    'product_id' => $product_id,
                    'product_owner_id' => $product_owner_id,
                    'status' => 'active',
                    'created_at' => current_time('mysql'),
                ),
                array('%d', '%d', '%d', '%s', '%s')
            );
            
            if ($result) {
                error_log('✅ Afiliación creada correctamente para producto ' . $product_id);
                $success_count++;
            } else {
                $error_msg = $wpdb->last_error ? $wpdb->last_error : 'Error desconocido';
                error_log('❌ Error al insertar afiliación: ' . $error_msg);
                error_log('SQL Query: ' . $wpdb->last_query);
                $errors[] = sprintf(__('Error al afiliar producto ID %d: %s', 'wcfm-product-affiliate'), $product_id, $error_msg);
            }
        }
        
        // NO remover del pool - es una tabla de referencia permanente
        // El admin puede afiliar los mismos productos a múltiples vendedores
        
        $message = sprintf(
            __('%d productos afiliados correctamente al vendedor', 'wcfm-product-affiliate'),
            $success_count
        );
        
        if ($success_count > 0) {
            $message .= '<br><br><em>' . __('Nota: Los productos permanecen en el pool para poder afiliarlos a otros vendedores.', 'wcfm-product-affiliate') . '</em>';
        }
        
        if (!empty($errors)) {
            $message .= '<br><br><strong>' . __('Errores:', 'wcfm-product-affiliate') . '</strong><br>' . implode('<br>', $errors);
        }
        
        wp_send_json_success(array(
            'message' => $message,
            'success_count' => $success_count,
            'errors' => $errors,
        ));
    }
}


