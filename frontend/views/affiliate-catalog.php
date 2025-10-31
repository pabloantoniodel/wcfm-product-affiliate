<?php
/**
 * Vista del Catálogo de Productos Afiliados
 *
 * @package WCFM_Product_Affiliate
 */

if (!defined('ABSPATH')) {
    exit;
}

// CSS para que el contenido ocupe todo el ancho y esté alineado a la izquierda
?>
<style>
    /* Contenedores al 100% del ancho */
    .wcfm-container,
    #wcfm-main-contentainer .wcfm-container,
    .wcfm-collapse .wcfm-container {
        max-width: 100% !important;
        width: 100% !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
        text-align: left !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    
    /* Títulos alineados a la izquierda */
    .wcfm-container h2,
    .wcfm-container h3 {
        text-align: left !important;
        margin-left: 0 !important;
        padding-left: 0 !important;
    }
    
    /* Tablas sin márgenes */
    .wcfm-table,
    .wcfm-container .wcfm-table,
    table.wcfm-table {
        margin: 0 !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
        width: 100% !important;
        text-align: left !important;
    }
    
    /* Divs de overflow al 100% */
    .wcfm-container > div {
        width: 100% !important;
        max-width: 100% !important;
    }
    
    /* Eliminar cualquier centrado */
    .wcfm-container * {
        text-align: inherit !important;
    }
    
    .wcfm-container td,
    .wcfm-container th {
        text-align: left !important;
    }
</style>
<?php

global $WCFMmp;
$vendor_id = get_current_user_id();

// Obtener afiliaciones actuales del vendedor
$current_affiliates = WCFM_Affiliate()->db->get_vendor_affiliates($vendor_id);
$affiliate_product_ids = array();
foreach ($current_affiliates as $affiliate) {
    $affiliate_product_ids[] = $affiliate->product_id;
}

// Obtener estadísticas
$stats = WCFM_Affiliate()->commission->get_vendor_earnings($vendor_id, 'completed');

// Parámetros de búsqueda
$search_term = isset($_GET['affiliate_search']) ? sanitize_text_field($_GET['affiliate_search']) : '';
$search_category = isset($_GET['affiliate_category']) ? intval($_GET['affiliate_category']) : 0;
$search_vendor = isset($_GET['affiliate_vendor']) ? intval($_GET['affiliate_vendor']) : 0;
$per_page = 20;
$paged = isset($_GET['affiliate_page']) ? max(1, intval($_GET['affiliate_page'])) : 1;

// Construir query de productos disponibles
$args = array(
    'post_type' => 'product',
    'posts_per_page' => $per_page,
    'paged' => $paged,
    'post_status' => 'publish',
    'author__not_in' => array($vendor_id),
    'post__not_in' => $affiliate_product_ids,
);

// Aplicar búsqueda mejorada (nombre, descripción, SKU, ID)
if (!empty($search_term)) {
    // Si es un número, buscar por ID primero
    if (is_numeric($search_term)) {
        $args['post__in'] = array(intval($search_term));
        // Si no encuentra por ID, buscar por texto
        $test_query = new WP_Query($args);
        if (!$test_query->have_posts()) {
            unset($args['post__in']);
            $args['s'] = $search_term;
            // Añadir búsqueda en SKU
            $args['meta_query'] = array(
                'relation' => 'OR',
                array(
                    'key' => '_sku',
                    'value' => $search_term,
                    'compare' => 'LIKE',
                ),
            );
        }
    } else {
        // Búsqueda por texto en título, contenido y SKU
        $args['s'] = $search_term;
        $args['meta_query'] = array(
            'relation' => 'OR',
            array(
                'key' => '_sku',
                'value' => $search_term,
                'compare' => 'LIKE',
            ),
        );
    }
}

// Aplicar filtro de categoría
if ($search_category) {
    $args['tax_query'] = array(
        array(
            'taxonomy' => 'product_cat',
            'field' => 'term_id',
            'terms' => $search_category,
        )
    );
}

// Aplicar filtro de vendedor
if ($search_vendor) {
    $args['author'] = $search_vendor;
}

$products = new WP_Query($args);
$total_pages = $products->max_num_pages;
?>

<div class="collapse wcfm-collapse" id="wcfm_affiliate_products_listing">
    
    <div class="wcfm-page-headig">
        <span class="wcfmfa fa-handshake-o"></span>
        <span class="wcfm-page-heading-text">Productos Afiliados</span>
        <?php
        // Incluir el header panel oficial de WCFM con notificaciones
        $wcfm_views_path = WP_PLUGIN_DIR . '/wc-frontend-manager/views/';
        if (file_exists($wcfm_views_path . 'wcfm-view-header-panels.php')) {
            include($wcfm_views_path . 'wcfm-view-header-panels.php');
        }
        ?>
    </div>
    
    <div class="wcfm-collapse-content">
        <div id="wcfm_page_load"></div>
        
        <?php
        // Verificar si el usuario ha ocultado las instrucciones
        $user_id = get_current_user_id();
        $hide_instructions = get_user_meta($user_id, '_wcfm_affiliate_hide_instructions', true);
        ?>
        
        <!-- Instrucciones de Uso -->
        <div class="wcfm-container" id="affiliate-instructions" style="margin-bottom: 20px; <?php echo ($hide_instructions ? 'display: none;' : ''); ?>">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3); position: relative;">
                <!-- Botón cerrar -->
                <button id="hide-instructions-btn" style="position: absolute; top: 15px; right: 15px; background: rgba(255,255,255,0.2); border: none; color: white; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; font-size: 18px; line-height: 1;" title="Ocultar instrucciones">
                    ×
                </button>
                
                <h2 style="margin-top: 0; color: white; display: flex; align-items: center; gap: 10px;">
                    <span class="dashicons dashicons-info" style="font-size: 28px;"></span>
                    ¿Cómo Funciona el Sistema de Afiliación?
                </h2>
                
                <div style="background: rgba(255,255,255,0.1); padding: 20px; border-radius: 8px; margin-top: 15px;">
                    <ol style="margin: 0; padding-left: 20px; line-height: 1.8;">
                        <li><strong>Busca y selecciona</strong> productos de otros vendedores que quieras vender usando el buscador abajo.</li>
                        <li><strong>Añádelos a tu tienda</strong> haciendo clic en "Añadir" (uno por uno) o selecciona varios y usa "Añadir Seleccionados".</li>
                        <li><strong>Los productos aparecerán</strong> automáticamente en tu escaparate público junto a tus productos propios.</li>
                        <li><strong>Cuando un cliente compre</strong> desde tu tienda, el pedido irá al propietario original del producto.</li>
                        <li><strong>Recibirás tu comisión</strong> automáticamente (<?php echo WCFM_Affiliate()->get_option('default_commission_rate', 1); ?>% del total de venta).</li>
                        <li><strong>No gestionas nada</strong> - Solo ganas comisión. El propietario se encarga del envío y atención.</li>
                    </ol>
                </div>
                
                <div style="margin-top: 15px; padding: 15px; background: rgba(255,255,255,0.1); border-radius: 8px; border-left: 4px solid rgba(255,255,255,0.5);">
                    <strong>💡 Ventaja Principal:</strong> Amplía tu catálogo sin inventario ni gestión. Gana comisiones pasivas.
                </div>
                
                <div style="margin-top: 15px; display: flex; gap: 10px; align-items: center;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; background: rgba(255,255,255,0.2); padding: 8px 15px; border-radius: 20px;">
                        <input type="checkbox" id="dont-show-again-checkbox" style="width: 16px; height: 16px; cursor: pointer;">
                        <span>No volver a mostrar estas instrucciones</span>
                    </label>
                </div>
            </div>
        </div>
        
        <!-- Botón para mostrar instrucciones de nuevo -->
        <div class="wcfm-container" id="show-instructions-btn-container" style="margin-bottom: 20px; <?php echo (!$hide_instructions ? 'display: none;' : ''); ?>">
            <button id="show-instructions-btn" style="background: #667eea; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 500;">
                <span class="dashicons dashicons-info" style="vertical-align: middle;"></span> Mostrar Instrucciones
            </button>
        </div>
        
        <!-- Estadísticas -->
        <div class="wcfm-container" style="margin-bottom: 20px;">
            <h2>Mis Estadísticas de Afiliación</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 8px; color: white; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <div style="font-size: 12px; opacity: 0.9;">Productos Activos</div>
                    <div style="font-size: 32px; font-weight: bold; margin-top: 5px;"><?php echo count($current_affiliates); ?></div>
                </div>
                <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 20px; border-radius: 8px; color: white; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <div style="font-size: 12px; opacity: 0.9;">Ventas Totales</div>
                    <div style="font-size: 32px; font-weight: bold; margin-top: 5px;"><?php echo intval($stats->total_sales); ?></div>
                </div>
                <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); padding: 20px; border-radius: 8px; color: white; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <div style="font-size: 12px; opacity: 0.9;">Ganancias Totales</div>
                    <div style="font-size: 28px; font-weight: bold; margin-top: 5px;"><?php echo wc_price($stats->total_earnings); ?></div>
                </div>
            </div>
        </div>
        
        <!-- Productos Afiliados Actuales -->
        <?php if (!empty($current_affiliates)): ?>
        <div class="wcfm-container" style="margin-bottom: 30px; max-width: 100% !important; width: 100% !important;">
            <h2 style="text-align: left; margin-left: 0;">Mis Productos Afiliados</h2>
            
            <!-- Buscador para Mis Productos -->
            <div style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                <div style="position: relative;">
                    <label for="search-my-products" style="display: block; font-weight: 600; margin-bottom: 8px; color: #333;">
                        🔍 Buscar en: Producto, Propietario o SKU
                    </label>
                    <input 
                        type="text" 
                        id="search-my-products" 
                        placeholder="Escribe al menos 3 caracteres para buscar..." 
                        style="width: 100%; padding: 12px 45px 12px 15px; border: 2px solid #dee2e6; border-radius: 8px; font-size: 16px; box-sizing: border-box; transition: border-color 0.3s;"
                    >
                    <span id="my-products-search-loading" style="display: none; position: absolute; right: 15px; top: 43px; color: #2271b1;">
                        <span class="dashicons dashicons-update" style="animation: spin 1s linear infinite;"></span>
                    </span>
                    <div id="my-products-search-status" style="margin-top: 8px; font-size: 14px; color: #666;"></div>
                </div>
            </div>
            
            <div style="overflow-x: auto;">
                <table class="wcfm-table" style="width: 100%;">
                    <thead>
                        <tr style="background: #f8f9fa;">
                            <th style="padding: 12px;">Producto</th>
                            <th style="padding: 12px;">Propietario</th>
                            <th style="padding: 12px;">Comisión</th>
                            <th style="padding: 12px;">Estado</th>
                            <th style="padding: 12px;">Añadido</th>
                            <th style="padding: 12px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="my-products-tbody">
                        <?php foreach ($current_affiliates as $affiliate): ?>
                            <?php
                            $product = wc_get_product($affiliate->product_id);
                            $owner = get_userdata($affiliate->product_owner_id);
                            
                            if ($product) {
                                // Obtener imagen ajustada
                                $image = $product->get_image('thumbnail');
                                $image = str_replace('width="300"', 'width="80"', $image);
                                $image = str_replace('height="300"', 'height="80"', $image);
                            }
                            ?>
                            <tr style="border-bottom: 1px solid #e9ecef;">
                                <td style="padding: 12px;">
                                    <?php if ($product): ?>
                                        <div style="display: flex; align-items: flex-start; gap: 12px;">
                                            <div style="flex-shrink: 0; width: 80px;">
                                                <?php echo $image; ?>
                                            </div>
                                            <div style="flex: 1; min-width: 0;">
                                                <a href="<?php echo get_permalink($product->get_id()); ?>" target="_blank" style="font-weight: 500; color: #333; text-decoration: none; display: block; margin-bottom: 6px;">
                                                    <?php echo $product->get_name(); ?>
                                                </a>
                                                <?php if ($product->get_short_description()): ?>
                                                    <div style="font-size: 13px; color: #666; margin-bottom: 6px; line-height: 1.4;">
                                                        <?php echo wp_trim_words($product->get_short_description(), 15, '...'); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($product->get_sku()): ?>
                                                    <div style="font-size: 11px; color: #999;">
                                                        SKU: <?php echo $product->get_sku(); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div style="font-size: 13px; color: #2271b1; margin-top: 6px;">
                                                    <strong><?php echo $product->get_price_html(); ?></strong>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: #999;">Producto no encontrado</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px;">
                                    <?php if ($owner): ?>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <?php echo get_avatar($owner->ID, 32, '', '', array('style' => 'border-radius: 50%;')); ?>
                                            <span><?php echo esc_html($owner->display_name); ?></span>
                                        </div>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px;">
                                    <strong style="color: #28a745; font-size: 16px;"><?php echo floatval($affiliate->commission_rate); ?>%</strong>
                                </td>
                                <td style="padding: 12px;">
                                    <span style="background: #28a745; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px;">
                                        <?php echo esc_html(ucfirst($affiliate->status)); ?>
                                    </span>
                                </td>
                                <td style="padding: 12px;"><?php echo date_i18n('d/m/Y', strtotime($affiliate->created_at)); ?></td>
                                <td style="padding: 12px;">
                                    <a href="#" class="wcfm_affiliate_remove_button" data-product-id="<?php echo esc_attr($affiliate->product_id); ?>" style="color: #dc3545; text-decoration: none; font-weight: 500;" title="Eliminar de mi tienda">
                                        <span class="wcfmfa fa-trash"></span> Eliminar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Buscador y Filtros -->
        <div class="wcfm-container" style="margin-bottom: 20px;">
            <h2>Buscar Productos para Vender</h2>
            <p style="color: #666; margin-bottom: 20px;">Encuentra productos de otros vendedores para añadir a tu tienda y ganar comisiones por cada venta.</p>
            
            <form id="affiliate-search-form" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <div style="display: grid; grid-template-columns: 1fr; gap: 15px; align-items: end;">
                    <!-- Buscador de texto -->
                    <div style="position: relative;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #333;">
                            🔍 Buscar en: Nombre, Descripción, SKU
                        </label>
                        <input type="text" 
                               name="affiliate_search" 
                               id="affiliate_search" 
                               value="<?php echo esc_attr($search_term); ?>" 
                               placeholder="Escribe al menos 3 caracteres para buscar..." 
                               style="width: 100%; padding: 12px 15px; border: 2px solid #dee2e6; border-radius: 8px; font-size: 16px; box-sizing: border-box; transition: border-color 0.3s;">
                        <?php if (!empty($search_term)): ?>
                        <button type="button" id="affiliate_clear_search" class="button" style="margin-top: 10px; padding: 10px 20px; font-size: 14px;">
                            <span class="dashicons dashicons-no" style="margin-right: 5px; vertical-align: middle;"></span>
                            Limpiar búsqueda
                        </button>
                        <?php endif; ?>
                        <span id="products-search-loading" style="display: none; position: absolute; right: 15px; top: 43px; color: #2271b1;">
                            <span class="dashicons dashicons-update" style="animation: spin 1s linear infinite;"></span>
                        </span>
                        <div id="products-search-status" style="margin-top: 8px; font-size: 14px; color: #666;"></div>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Productos Disponibles -->
        <div class="wcfm-container" style="max-width: 100% !important; width: 100% !important;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0; text-align: left;">Productos Disponibles <?php if (!empty($search_term)) echo '- Resultados para: "' . esc_html($search_term) . '"'; ?></h2>
                <button id="wcfm_bulk_add_affiliates" class="wcfm_submit_button" style="background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 500;">
                    <span class="wcfmfa fa-plus-circle"></span> Añadir Seleccionados
                </button>
            </div>
            
            <p style="color: #666; margin-bottom: 20px;">
                Navega y añade productos de otros vendedores a tu tienda. Ganarás una comisión por cada venta.
            </p>
            
            <?php if ($products->have_posts()): ?>
                <div style="overflow-x: auto;">
                    <table class="wcfm-table" style="width: 100%;">
                        <thead>
                            <tr style="background: #f8f9fa;">
                                <th style="padding: 12px; width: 40px;">
                                    <input type="checkbox" id="select-all-affiliates" title="Seleccionar todos">
                                </th>
                                <th style="padding: 12px;">Producto</th>
                                <th style="padding: 12px; width: 120px;">Precio</th>
                                <th style="padding: 12px; width: 150px;">Propietario</th>
                                <th style="padding: 12px; width: 120px;">Tu Comisión</th>
                                <th style="padding: 12px; width: 150px;">Categoría</th>
                                <th style="padding: 12px; width: 100px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="products-available-tbody">
                            <?php while ($products->have_posts()): $products->the_post(); ?>
                                <?php
                                $product = wc_get_product(get_the_ID());
                                $product_id = get_the_ID();
                                $owner = get_userdata(get_the_author_meta('ID'));
                                $default_commission = WCFM_Affiliate()->get_option('default_commission_rate', 1);
                                
                                // Obtener categorías
                                $terms = get_the_terms($product_id, 'product_cat');
                                $categories_names = array();
                                if ($terms && !is_wp_error($terms)) {
                                    foreach ($terms as $term) {
                                        $categories_names[] = $term->name;
                                    }
                                }
                                $categories_str = !empty($categories_names) ? implode(', ', $categories_names) : '-';
                                
                                // Calcular ganancia estimada
                                $price = $product->get_price();
                                $estimated_commission = $price ? ($price * $default_commission / 100) : 0;
                                ?>
                                <tr style="border-bottom: 1px solid #e9ecef;">
                                    <td style="padding: 12px; text-align: center;">
                                        <input type="checkbox" class="affiliate-product-checkbox" value="<?php echo $product_id; ?>">
                                    </td>
                                    <td style="padding: 12px;">
                                        <div style="display: flex; align-items: flex-start; gap: 12px;">
                                            <div style="flex-shrink: 0; width: 80px;">
                                                <?php 
                                                $image = $product->get_image('thumbnail');
                                                // Modificar imagen para hacerla más pequeña
                                                $image = str_replace('width="300"', 'width="80"', $image);
                                                $image = str_replace('height="300"', 'height="80"', $image);
                                                echo $image;
                                                ?>
                                            </div>
                                            <div style="flex: 1; min-width: 0;">
                                                <a href="<?php the_permalink(); ?>" target="_blank" style="font-weight: 500; color: #333; text-decoration: none; display: block; margin-bottom: 6px;">
                                                    <?php the_title(); ?>
                                                </a>
                                                <?php if ($product->get_short_description()): ?>
                                                    <div style="font-size: 13px; color: #666; margin-bottom: 6px; line-height: 1.4;">
                                                        <?php echo wp_trim_words($product->get_short_description(), 15, '...'); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($product->get_sku()): ?>
                                                    <div style="font-size: 11px; color: #999;">
                                                        SKU: <?php echo $product->get_sku(); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding: 12px;">
                                        <strong style="color: #333;"><?php echo $product->get_price_html(); ?></strong>
                                    </td>
                                    <td style="padding: 12px;">
                                        <?php if ($owner): ?>
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <?php echo get_avatar($owner->ID, 32, '', '', array('style' => 'border-radius: 50%;')); ?>
                                                <span><?php echo esc_html($owner->display_name); ?></span>
                                            </div>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 12px;">
                                        <div>
                                            <strong style="color: #28a745; font-size: 16px;"><?php echo $default_commission; ?>%</strong>
                                        </div>
                                        <?php if ($estimated_commission > 0): ?>
                                            <div style="font-size: 12px; color: #666; margin-top: 4px;">
                                                ~<?php echo wc_price($estimated_commission); ?> / venta
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 12px; font-size: 13px; color: #666;">
                                        <?php echo esc_html($categories_str); ?>
                                    </td>
                                    <td style="padding: 12px; text-align: center;">
                                        <a href="#" 
                                           class="wcfm_affiliate_add_button" 
                                           data-product-id="<?php echo $product_id; ?>" 
                                           style="background: #667eea; color: white; padding: 8px 16px; border-radius: 4px; text-decoration: none; display: inline-block; font-size: 13px; font-weight: 500;"
                                           title="Añadir a mi tienda">
                                            <span class="wcfmfa fa-plus-circle"></span> Añadir
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginación -->
                <?php if ($total_pages > 1): ?>
                    <div style="margin-top: 20px; text-align: center;">
                        <div class="wcfm-pagination" style="display: inline-flex; gap: 5px; align-items: center;">
                            <?php if ($paged > 1): ?>
                                <a href="#" class="affiliate-pagination" data-page="<?php echo ($paged - 1); ?>" 
                                   style="padding: 8px 12px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #333;">
                                    ← Anterior
                                </a>
                            <?php endif; ?>
                            
                            <span style="padding: 8px 12px;">
                                Página <?php echo $paged; ?> de <?php echo $total_pages; ?>
                            </span>
                            
                            <?php if ($paged < $total_pages): ?>
                                <a href="#" class="affiliate-pagination" data-page="<?php echo ($paged + 1); ?>" 
                                   style="padding: 8px 12px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #333;">
                                    Siguiente →
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php wp_reset_postdata(); ?>
            <?php else: ?>
                <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 20px; border-radius: 4px;">
                    <h3 style="margin-top: 0;">No se encontraron productos</h3>
                    <p style="margin-bottom: 0;">
                        <?php if (!empty($search_term) || $search_category || $search_vendor): ?>
                            No hay productos que coincidan con tu búsqueda. Intenta con otros filtros.
                        <?php else: ?>
                            No hay productos disponibles en este momento o ya estás vendiendo todos los productos disponibles.
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
        
    </div>
</div>

<style>
.wcfm-table {
    border-collapse: collapse;
    background: white;
}
.wcfm-table th {
    text-align: left;
    font-weight: 600;
    color: #333;
}
.wcfm-table tbody tr {
    cursor: pointer;
    transition: background-color 0.2s;
}
.wcfm-table tbody tr:hover {
    background: #f0f8ff !important;
}
.wcfm-table tbody tr.selected {
    background: #e3f2fd !important;
}
.wcfm_affiliate_add_button:hover,
.wcfm_affiliate_remove_button:hover {
    opacity: 0.8;
}
/* Mejorar checkboxes - Forzar estilos */
.affiliate-product-checkbox,
#select-all-affiliates {
    width: 18px !important;
    height: 18px !important;
    cursor: pointer !important;
    opacity: 1 !important;
    position: relative !important;
    display: inline-block !important;
    visibility: visible !important;
    margin: 0 !important;
    -webkit-appearance: checkbox !important;
    -moz-appearance: checkbox !important;
    appearance: checkbox !important;
}
/* Hacer que la celda del checkbox también sea clickeable */
.wcfm-table td:has(.affiliate-product-checkbox),
.wcfm-table th:has(#select-all-affiliates) {
    cursor: pointer;
    text-align: center !important;
}
/* Asegurar que los checkboxes sean visibles */
input[type="checkbox"].affiliate-product-checkbox,
input[type="checkbox"]#select-all-affiliates {
    display: inline-block !important;
    visibility: visible !important;
    opacity: 1 !important;
}

/* Estilos para buscador AJAX */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

#affiliate_search:focus,
#search-my-products:focus {
    border-color: #2271b1 !important;
    outline: none;
}
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    'use strict';
    
    console.log('Affiliate script loaded');
    console.log('Checkboxes found:', $('.affiliate-product-checkbox').length);
    console.log('jQuery version:', $.fn.jquery);
    
    // Test inicial de checkboxes
    setTimeout(function() {
        console.log('After timeout - Checkboxes:', $('.affiliate-product-checkbox').length);
        console.log('Select all checkbox:', $('#select-all-affiliates').length);
    }, 1000);
    
    // Función para construir URL con parámetros de búsqueda
    function buildSearchUrl(page) {
        var baseUrl = window.location.href.split('?')[0];
        var params = [];
        
        var search = $('#affiliate_search').val();
        var category = $('#affiliate_category').val();
        var vendor = $('#affiliate_vendor').val();
        
        if (search) params.push('affiliate_search=' + encodeURIComponent(search));
        if (category) params.push('affiliate_category=' + category);
        if (vendor) params.push('affiliate_vendor=' + vendor);
        if (page) params.push('affiliate_page=' + page);
        
        return baseUrl + (params.length > 0 ? '?' + params.join('&') : '');
    }
    
    // Búsqueda AJAX en tiempo real
    var searchTimeout;
    var originalProductRows = $('#products-available-tbody').html();
    
    $('#affiliate_search').on('input', function() {
        clearTimeout(searchTimeout);
        var searchTerm = $(this).val().trim();
        
        // Si está vacío, restaurar tabla original
        if (searchTerm === '') {
            $('#products-available-tbody').html(originalProductRows);
            $('#products-search-status').html('');
            $('#products-search-loading').hide();
            return;
        }
        
        // Si tiene menos de 3 caracteres, mostrar mensaje
        if (searchTerm.length < 3) {
            $('#products-search-status').html('<span style="color: #f0b849;">⚠️ Escribe al menos 3 caracteres para buscar</span>');
            return;
        }
        
        // Esperar 500ms después de que el usuario deje de escribir
        searchTimeout = setTimeout(function() {
            performProductsSearch(searchTerm);
        }, 500);
    });
    
    function performProductsSearch(searchTerm) {
        $('#products-search-loading').show();
        $('#products-search-status').html('<span style="color: #2271b1;">Buscando productos...</span>');
        
        $.ajax({
            url: wcfm_affiliate_params.ajax_url,
            type: 'POST',
            data: {
                action: 'wcfm_affiliate_search_products',
                nonce: wcfm_affiliate_params.nonce,
                search: searchTerm
            },
            success: function(response) {
                $('#products-search-loading').hide();
                
                if (response.success) {
                    $('#products-available-tbody').html(response.data.html);
                    if (response.data.count > 0) {
                        $('#products-search-status').html('<span style="color: #00a32a;">✓ ' + response.data.count + ' producto(s) encontrado(s)</span>');
                    } else {
                        $('#products-search-status').html('<span style="color: #999;">No se encontraron productos</span>');
                    }
                    
                    // Re-bind event handlers for new rows
                    initializeAffiliateButtons();
                    initializeSelectAll();
                } else {
                    $('#products-search-status').html('<span style="color: #d63638;">Error en la búsqueda</span>');
                }
            },
            error: function() {
                $('#products-search-loading').hide();
                $('#products-search-status').html('<span style="color: #d63638;">Error al conectar con el servidor</span>');
            }
        });
    }
    
    // Búsqueda AJAX para "Mis Productos Afiliados"
    var myProductsSearchTimeout;
    var originalMyProductRows = $('#my-products-tbody').html();
    
    $('#search-my-products').on('input', function() {
        clearTimeout(myProductsSearchTimeout);
        var searchTerm = $(this).val().trim();
        
        // Si está vacío, restaurar tabla original
        if (searchTerm === '') {
            $('#my-products-tbody').html(originalMyProductRows);
            $('#my-products-search-status').html('');
            $('#my-products-search-loading').hide();
            return;
        }
        
        // Si tiene menos de 3 caracteres, mostrar mensaje
        if (searchTerm.length < 3) {
            $('#my-products-search-status').html('<span style="color: #f0b849;">⚠️ Escribe al menos 3 caracteres para buscar</span>');
            return;
        }
        
        // Esperar 500ms después de que el usuario deje de escribir
        myProductsSearchTimeout = setTimeout(function() {
            performMyProductsSearch(searchTerm);
        }, 500);
    });
    
    function performMyProductsSearch(searchTerm) {
        $('#my-products-search-loading').show();
        $('#my-products-search-status').html('<span style="color: #2271b1;">Buscando...</span>');
        
        $.ajax({
            url: wcfm_affiliate_params.ajax_url,
            type: 'POST',
            data: {
                action: 'wcfm_affiliate_search_my_products',
                nonce: wcfm_affiliate_params.nonce,
                search: searchTerm
            },
            success: function(response) {
                $('#my-products-search-loading').hide();
                
                if (response.success) {
                    $('#my-products-tbody').html(response.data.html);
                    if (response.data.count > 0) {
                        $('#my-products-search-status').html('<span style="color: #00a32a;">✓ ' + response.data.count + ' producto(s) encontrado(s)</span>');
                    } else {
                        $('#my-products-search-status').html('<span style="color: #999;">No se encontraron productos</span>');
                    }
                    
                    // Re-bind event handlers for remove buttons
                    initializeRemoveButtons();
                } else {
                    $('#my-products-search-status').html('<span style="color: #d63638;">Error en la búsqueda</span>');
                }
            },
            error: function() {
                $('#my-products-search-loading').hide();
                $('#my-products-search-status').html('<span style="color: #d63638;">Error al conectar con el servidor</span>');
            }
        });
    }
    
    // Manejar ocultamiento de instrucciones
    $('#hide-instructions-btn').on('click', function(e) {
        e.preventDefault();
        
        var dontShowAgain = $('#dont-show-again-checkbox').prop('checked');
        
        // Ocultar panel de instrucciones
        $('#affiliate-instructions').fadeOut();
        
        // Mostrar botón de "Mostrar instrucciones"
        $('#show-instructions-btn-container').fadeIn();
        
        // Si marcó "No volver a mostrar", guardar en BD
        if (dontShowAgain) {
            $.ajax({
                url: wcfm_affiliate_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcfm_affiliate_hide_instructions',
                    nonce: wcfm_affiliate_params.nonce,
                    hide: true
                },
                success: function(response) {
                    console.log('Instructions hidden permanently');
                }
            });
        }
    });
    
    // Mostrar instrucciones de nuevo
    $('#show-instructions-btn').on('click', function(e) {
        e.preventDefault();
        
        // Mostrar panel
        $('#affiliate-instructions').fadeIn();
        
        // Ocultar botón
        $('#show-instructions-btn-container').fadeOut();
        
        // Desmarcar checkbox
        $('#dont-show-again-checkbox').prop('checked', false);
        
        // Actualizar en BD para que vuelvan a mostrarse
        $.ajax({
            url: wcfm_affiliate_params.ajax_url,
            type: 'POST',
            data: {
                action: 'wcfm_affiliate_hide_instructions',
                nonce: wcfm_affiliate_params.nonce,
                hide: false
            },
            success: function(response) {
                console.log('Instructions shown again');
            }
        });
    });
    
    // Seleccionar/deseleccionar todos (delegado)
    $(document).on('change', '#select-all-affiliates', function() {
        var isChecked = $(this).prop('checked');
        $('.affiliate-product-checkbox').prop('checked', isChecked);
        updateBulkButton();
    });
    
    // Actualizar botón masivo al cambiar selección (delegado)
    $(document).on('change', '.affiliate-product-checkbox', function() {
        console.log('Checkbox changed:', $(this).prop('checked'));
        updateBulkButton();
        
        // Añadir clase visual a la fila
        var $row = $(this).closest('tr');
        if ($(this).prop('checked')) {
            $row.addClass('selected');
            console.log('Row marked as selected');
        } else {
            $row.removeClass('selected');
            console.log('Row unmarked');
        }
        
        // Actualizar checkbox de "seleccionar todos"
        var total = $('.affiliate-product-checkbox').length;
        var checked = $('.affiliate-product-checkbox:checked').length;
        $('#select-all-affiliates').prop('checked', total === checked);
        console.log('Selected:', checked, 'of', total);
    });
    
    // Manejar clicks en la fila de la tabla
    $(document).on('click', '.wcfm-table tbody tr', function(e) {
        // Solo si no se hizo click en un enlace o botón
        if (!$(e.target).is('a, button, input, .wcfm_affiliate_add_button, .wcfm_affiliate_add_button *')) {
            var $checkbox = $(this).find('.affiliate-product-checkbox');
            if ($checkbox.length) {
                $checkbox.prop('checked', !$checkbox.prop('checked')).trigger('change');
            }
        }
    });
    
    // Prevenir que el click en checkbox propague al tr
    $(document).on('click', '.affiliate-product-checkbox', function(e) {
        e.stopPropagation();
    });
    
    // Función para actualizar texto del botón masivo
    function updateBulkButton() {
        var count = $('.affiliate-product-checkbox:checked').length;
        var $button = $('#wcfm_bulk_add_affiliates');
        
        if (count > 0) {
            $button.html('<span class="wcfmfa fa-plus-circle"></span> Añadir ' + count + ' Seleccionado(s)');
            $button.css('background', '#28a745');
        } else {
            $button.html('<span class="wcfmfa fa-plus-circle"></span> Añadir Seleccionados');
            $button.css('background', '#667eea');
        }
    }
});
</script>
