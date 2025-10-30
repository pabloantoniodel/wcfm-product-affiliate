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
        
        // AJAX handlers
        add_action('wp_ajax_wcfm_affiliate_search_products', array($this, 'ajax_search_products'));
        add_action('wp_ajax_wcfm_affiliate_add_to_pool', array($this, 'ajax_add_to_pool'));
        add_action('wp_ajax_wcfm_affiliate_remove_from_pool', array($this, 'ajax_remove_from_pool'));
        add_action('wp_ajax_wcfm_affiliate_search_vendors', array($this, 'ajax_search_vendors'));
        add_action('wp_ajax_wcfm_affiliate_bulk_affiliate', array($this, 'ajax_bulk_affiliate'));
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
                        <h3><?php _e('Resultados de búsqueda', 'wcfm-product-affiliate'); ?></h3>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Producto', 'wcfm-product-affiliate'); ?></th>
                                    <th><?php _e('Vendedor', 'wcfm-product-affiliate'); ?></th>
                                    <th><?php _e('Precio', 'wcfm-product-affiliate'); ?></th>
                                    <th><?php _e('Acción', 'wcfm-product-affiliate'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="search-results-body"></tbody>
                        </table>
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
                                    <th><?php _e('Vendedor', 'wcfm-product-affiliate'); ?></th>
                                    <th><?php _e('Email', 'wcfm-product-affiliate'); ?></th>
                                    <th><?php _e('Productos', 'wcfm-product-affiliate'); ?></th>
                                    <th><?php _e('Acción', 'wcfm-product-affiliate'); ?></th>
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
        
        ?>
        <tr data-product-id="<?php echo esc_attr($product_id); ?>">
            <td class="check-column">
                <input type="checkbox" class="product-checkbox" value="<?php echo esc_attr($product_id); ?>" />
            </td>
            <td><?php echo $product->get_image('thumbnail'); ?></td>
            <td>
                <strong><?php echo esc_html($product->get_name()); ?></strong><br>
                <small>ID: <?php echo $product_id; ?></small>
            </td>
            <td><?php echo esc_html($vendor->display_name); ?></td>
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
        // Log para debug
        error_log('WCFM Affiliate: ajax_search_products called');
        error_log('POST data: ' . print_r($_POST, true));
        
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wcfm_affiliate_bulk_nonce')) {
            error_log('WCFM Affiliate: Nonce verification failed');
            wp_send_json_error(array('message' => __('Nonce inválido', 'wcfm-product-affiliate')));
            return;
        }
        
        // Verificar permisos
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Sin permisos', 'wcfm-product-affiliate')));
            return;
        }
        
        try {
            $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
            
            $args = array(
                'post_type' => 'product',
                'post_status' => 'publish',
                'posts_per_page' => 20,
                's' => $search,
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
            
            $products = get_posts($args);
            $results = array();
            
            foreach ($products as $post) {
                $product = wc_get_product($post->ID);
                if (!$product) {
                    continue;
                }
                
                $vendor_id = get_post_field('post_author', $post->ID);
                $vendor = get_userdata($vendor_id);
                
                $results[] = array(
                    'id' => $post->ID,
                    'name' => $product->get_name(),
                    'vendor' => $vendor ? $vendor->display_name : 'Desconocido',
                    'price' => $product->get_price_html(),
                    'image' => $product->get_image('thumbnail'),
                );
            }
            
            wp_send_json_success(array('products' => $results));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Error: ' . $e->getMessage()));
        }
    }
    
    /**
     * AJAX: Add to pool
     */
    public function ajax_add_to_pool() {
        check_ajax_referer('wcfm_affiliate_bulk_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Sin permisos', 'wcfm-product-affiliate')));
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
        check_ajax_referer('wcfm_affiliate_bulk_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Sin permisos', 'wcfm-product-affiliate')));
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
        check_ajax_referer('wcfm_affiliate_bulk_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Sin permisos', 'wcfm-product-affiliate')));
        }
        
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = 10;
        
        $args = array(
            'role' => 'wcfm_vendor',
            'number' => $per_page,
            'offset' => ($page - 1) * $per_page,
            'search' => '*' . $search . '*',
            'search_columns' => array('user_login', 'user_email', 'display_name'),
        );
        
        $user_query = new WP_User_Query($args);
        $vendors = $user_query->get_results();
        $total = $user_query->get_total();
        
        $results = array();
        foreach ($vendors as $vendor) {
            $product_count = count_user_posts($vendor->ID, 'product');
            
            $results[] = array(
                'id' => $vendor->ID,
                'name' => $vendor->display_name,
                'email' => $vendor->user_email,
                'products' => $product_count,
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
        check_ajax_referer('wcfm_affiliate_bulk_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Sin permisos', 'wcfm-product-affiliate')));
        }
        
        $product_ids = isset($_POST['product_ids']) ? array_map('intval', $_POST['product_ids']) : array();
        $vendor_id = isset($_POST['vendor_id']) ? intval($_POST['vendor_id']) : 0;
        
        if (empty($product_ids)) {
            wp_send_json_error(array('message' => __('No se especificaron productos', 'wcfm-product-affiliate')));
        }
        
        if (!$vendor_id) {
            wp_send_json_error(array('message' => __('No se especificó vendedor', 'wcfm-product-affiliate')));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'wcfm_affiliate_products';
        
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
            
            // Crear afiliación
            $result = $wpdb->insert(
                $table_name,
                array(
                    'product_id' => $product_id,
                    'vendor_id' => $vendor_id,
                    'status' => 'active',
                    'created_at' => current_time('mysql'),
                ),
                array('%d', '%d', '%s', '%s')
            );
            
            if ($result) {
                $success_count++;
            } else {
                $errors[] = sprintf(__('Error al afiliar producto ID %d', 'wcfm-product-affiliate'), $product_id);
            }
        }
        
        // Remover productos afiliados del pool
        if ($success_count > 0) {
            $pool = $this->get_pool();
            $pool = array_diff($pool, $product_ids);
            $this->update_pool($pool);
        }
        
        $message = sprintf(
            __('%d productos afiliados correctamente', 'wcfm-product-affiliate'),
            $success_count
        );
        
        if (!empty($errors)) {
            $message .= '<br><strong>' . __('Errores:', 'wcfm-product-affiliate') . '</strong><br>' . implode('<br>', $errors);
        }
        
        wp_send_json_success(array(
            'message' => $message,
            'success_count' => $success_count,
            'errors' => $errors,
        ));
    }
}


