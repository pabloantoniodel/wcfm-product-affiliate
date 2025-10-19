<?php
/**
 * Vista del Catálogo de Productos Afiliados
 *
 * @package WCFM_Product_Affiliate
 */

if (!defined('ABSPATH')) {
    exit;
}

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

// Aplicar búsqueda
if (!empty($search_term)) {
    $args['s'] = $search_term;
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
        <?php do_action('wcfm_page_heading'); ?>
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
        <div class="wcfm-container" style="margin-bottom: 30px;">
            <h2>Mis Productos Afiliados</h2>
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
                    <tbody>
                        <?php foreach ($current_affiliates as $affiliate): ?>
                            <?php
                            $product = wc_get_product($affiliate->product_id);
                            $owner = get_userdata($affiliate->product_owner_id);
                            ?>
                            <tr>
                                <td style="padding: 12px;">
                                    <?php if ($product): ?>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <?php echo $product->get_image('thumbnail'); ?>
                                            <a href="<?php echo get_permalink($product->get_id()); ?>" target="_blank" style="font-weight: 500;">
                                                <?php echo $product->get_name(); ?>
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: #999;">Producto no encontrado</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px;"><?php echo $owner ? esc_html($owner->display_name) : '-'; ?></td>
                                <td style="padding: 12px;"><strong style="color: #2ecc71;"><?php echo floatval($affiliate->commission_rate); ?>%</strong></td>
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
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
                    <!-- Buscador de texto -->
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Buscar producto</label>
                        <input type="text" 
                               name="affiliate_search" 
                               id="affiliate_search" 
                               value="<?php echo esc_attr($search_term); ?>" 
                               placeholder="Nombre, descripción, SKU..." 
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    
                    <!-- Filtro de categoría -->
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Categoría</label>
                        <select name="affiliate_category" id="affiliate_category" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="">Todas las categorías</option>
                            <?php
                            $categories = get_terms(array(
                                'taxonomy' => 'product_cat',
                                'hide_empty' => true,
                            ));
                            foreach ($categories as $category) {
                                $selected = ($search_category == $category->term_id) ? 'selected' : '';
                                echo '<option value="' . $category->term_id . '" ' . $selected . '>' . esc_html($category->name) . ' (' . $category->count . ')</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <!-- Filtro de vendedor -->
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Vendedor</label>
                        <select name="affiliate_vendor" id="affiliate_vendor" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="">Todos los vendedores</option>
                            <?php
                            $vendors = get_users(array(
                                'role__in' => array('wcfm_vendor', 'seller', 'vendor'),
                                'exclude' => array($vendor_id),
                            ));
                            foreach ($vendors as $vendor_user) {
                                $selected = ($search_vendor == $vendor_user->ID) ? 'selected' : '';
                                echo '<option value="' . $vendor_user->ID . '" ' . $selected . '>' . esc_html($vendor_user->display_name) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <!-- Botones -->
                    <div style="display: flex; gap: 10px;">
                        <button type="button" id="affiliate_search_btn" style="background: #667eea; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 500;">
                            <span class="wcfmfa fa-search"></span> Buscar
                        </button>
                        <button type="button" id="affiliate_clear_btn" style="background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 500;">
                            <span class="wcfmfa fa-refresh"></span> Limpiar
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Productos Disponibles -->
        <div class="wcfm-container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0;">Productos Disponibles <?php if (!empty($search_term)) echo '- Resultados para: "' . esc_html($search_term) . '"'; ?></h2>
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
                        <tbody>
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
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <div style="flex-shrink: 0;">
                                                <?php echo $product->get_image('thumbnail'); ?>
                                            </div>
                                            <div>
                                                <a href="<?php the_permalink(); ?>" target="_blank" style="font-weight: 500; color: #333; text-decoration: none;">
                                                    <?php the_title(); ?>
                                                </a>
                                                <?php if ($product->get_sku()): ?>
                                                    <div style="font-size: 12px; color: #999; margin-top: 4px;">
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
    
    // Manejar búsqueda
    $('#affiliate_search_btn').on('click', function(e) {
        e.preventDefault();
        console.log('Search button clicked');
        window.location.href = buildSearchUrl(1);
    });
    
    // Limpiar búsqueda
    $('#affiliate_clear_btn').on('click', function(e) {
        e.preventDefault();
        console.log('Clear button clicked');
        var baseUrl = window.location.href.split('?')[0];
        window.location.href = baseUrl;
    });
    
    // Paginación
    $('.affiliate-pagination').on('click', function(e) {
        e.preventDefault();
        var page = $(this).data('page');
        console.log('Pagination clicked: page ' + page);
        window.location.href = buildSearchUrl(page);
    });
    
    // Permitir búsqueda con Enter
    $('#affiliate_search').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#affiliate_search_btn').click();
        }
    });
    
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
