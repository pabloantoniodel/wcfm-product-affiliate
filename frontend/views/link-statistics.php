<?php
/**
 * Link Statistics View
 * Shows statistics about shared affiliate links
 *
 * @package WCFM_Product_Affiliate
 */

if (!defined('ABSPATH')) {
    exit;
}

global $WCFM, $WCFMmp;

$vendor_id = get_current_user_id();
$link_tracking = new WCFM_Affiliate_Link_Tracking();

// Get date range from request
$date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : date('Y-m-d', strtotime('-30 days'));
$date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : date('Y-m-d');

// Get statistics
$stats = $link_tracking->get_vendor_link_stats($vendor_id, $date_from . ' 00:00:00', $date_to . ' 23:59:59');

// Get recent clicks (as affiliate - links I shared)
$recent_clicks = $link_tracking->get_vendor_link_clicks($vendor_id, array(
    'limit' => 20,
    'date_from' => $date_from . ' 00:00:00',
    'date_to' => $date_to . ' 23:59:59'
));

// Get statistics as product owner (links others shared of MY products)
$owner_stats = $link_tracking->get_product_owner_link_stats($vendor_id, $date_from . ' 00:00:00', $date_to . ' 23:59:59');

// Get clicks on my products from affiliates
$owner_clicks = $link_tracking->get_product_owner_link_clicks($vendor_id, array(
    'limit' => 20,
    'date_from' => $date_from . ' 00:00:00',
    'date_to' => $date_to . ' 23:59:59'
));

?>

<div class="collapse wcfm-collapse" id="wcfm_affiliate_link_stats_head">
    
    <div class="wcfm-page-headig">
        <span class="wcfmfa fa-link wcfm-heading-icon"></span>
        <span class="wcfm-heading-text"><?php _e('Enlaces', 'wcfm-product-affiliate'); ?></span>
        <?php
        // Incluir el header panel oficial de WCFM con notificaciones
        $wcfm_views_path = WP_PLUGIN_DIR . '/wc-frontend-manager/views/';
        if (file_exists($wcfm_views_path . 'wcfm-view-header-panels.php')) {
            include($wcfm_views_path . 'wcfm-view-header-panels.php');
        }
        ?>
    </div>
    <div class="wcfm-clearfix"></div>
    
    <!-- Date Filter -->
    <div class="wcfm-container wcfm-top-element-container">
        <div class="wcfm-content">
            <form method="get" class="wcfm-filter-form" style="margin-bottom: 15px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                
                <label style="font-weight: 600; margin-right: 10px;"><?php _e('Desde:', 'wcfm-product-affiliate'); ?></label>
                <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>" max="<?php echo date('Y-m-d'); ?>" style="margin-right: 20px; padding: 8px;">
                
                <label style="font-weight: 600; margin-right: 10px;"><?php _e('Hasta:', 'wcfm-product-affiliate'); ?></label>
                <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>" max="<?php echo date('Y-m-d'); ?>" style="margin-right: 20px; padding: 8px;">
                
                <button type="submit" class="wcfm_submit_button" onclick="return wcfm_filter_stats();">
                    <span class="wcfmfa fa-filter"></span> <?php _e('Filtrar', 'wcfm-product-affiliate'); ?>
                </button>
            </form>
        </div>
    </div>
    
    <!-- Statistics Summary -->
    <div class="wcfm-container">
        <div class="wcfm-content">
            <div class="wcfm-affiliate-stats-summary" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
                
                <!-- Total Clicks -->
                <div class="wcfm-stat-box" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <h3 style="margin: 0; font-size: 32px; color: #2271b1;">
                                <?php echo number_format($stats->total_clicks ?? 0); ?>
                            </h3>
                            <p style="margin: 5px 0 0; color: #666;">
                                <?php _e('Clics Totales', 'wcfm-product-affiliate'); ?>
                            </p>
                        </div>
                        <span class="dashicons dashicons-chart-line" style="font-size: 48px; color: #2271b1; opacity: 0.3;"></span>
                    </div>
                </div>
                
                <!-- Unique Products -->
                <div class="wcfm-stat-box" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <h3 style="margin: 0; font-size: 32px; color: #00a32a;">
                                <?php echo number_format($stats->unique_products ?? 0); ?>
                            </h3>
                            <p style="margin: 5px 0 0; color: #666;">
                                <?php _e('Productos Compartidos', 'wcfm-product-affiliate'); ?>
                            </p>
                        </div>
                        <span class="dashicons dashicons-products" style="font-size: 48px; color: #00a32a; opacity: 0.3;"></span>
                    </div>
                </div>
                
                <!-- Unique Visitors -->
                <div class="wcfm-stat-box" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <h3 style="margin: 0; font-size: 32px; color: #d63638;">
                                <?php echo number_format($stats->unique_visitors ?? 0); ?>
                            </h3>
                            <p style="margin: 5px 0 0; color: #666;">
                                <?php _e('Visitantes Únicos', 'wcfm-product-affiliate'); ?>
                            </p>
                        </div>
                        <span class="dashicons dashicons-groups" style="font-size: 48px; color: #d63638; opacity: 0.3;"></span>
                    </div>
                </div>
                
                <!-- Conversions -->
                <div class="wcfm-stat-box" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <h3 style="margin: 0; font-size: 32px; color: #f0b849;">
                                <?php echo number_format($stats->total_conversions ?? 0); ?>
                            </h3>
                            <p style="margin: 5px 0 0; color: #666;">
                                <?php _e('Conversiones a Venta', 'wcfm-product-affiliate'); ?>
                            </p>
                            <?php if (isset($stats->total_clicks) && $stats->total_clicks > 0): ?>
                                <small style="color: #999;">
                                    <?php echo number_format(($stats->total_conversions / $stats->total_clicks) * 100, 2); ?>% tasa de conversión
                                </small>
                            <?php endif; ?>
                        </div>
                        <span class="dashicons dashicons-cart" style="font-size: 48px; color: #f0b849; opacity: 0.3;"></span>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
    
    <!-- Recent Clicks Table -->
    <div class="wcfm-container">
        <div class="wcfm-content">
            <h2 style="margin-top: 0;"><?php _e('Clics Recientes', 'wcfm-product-affiliate'); ?></h2>
            
            <?php if (!empty($recent_clicks)): ?>
                <table class="wcfm-table" style="width: 100%;">
                    <thead>
                        <tr>
                            <th><?php _e('Fecha/Hora', 'wcfm-product-affiliate'); ?></th>
                            <th><?php _e('Producto', 'wcfm-product-affiliate'); ?></th>
                            <th><?php _e('Producto Original de', 'wcfm-product-affiliate'); ?></th>
                            <th><?php _e('Origen', 'wcfm-product-affiliate'); ?></th>
                            <th><?php _e('Estado', 'wcfm-product-affiliate'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_clicks as $click): ?>
                            <tr>
                                <td>
                                    <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($click->created_at)); ?>
                                </td>
                                <td>
                                    <a href="<?php echo get_permalink($click->product_id); ?>" target="_blank">
                                        <?php echo get_the_title($click->product_id); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php 
                                    $owner = get_userdata($click->product_owner_id);
                                    echo $owner ? esc_html($owner->display_name) : '-';
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($click->referrer_url) {
                                        $parsed = parse_url($click->referrer_url);
                                        echo esc_html($parsed['host'] ?? 'Directo');
                                    } else {
                                        _e('Directo', 'wcfm-product-affiliate');
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($click->converted_to_sale): ?>
                                        <span style="color: #00a32a;">✓ <?php _e('Convertido', 'wcfm-product-affiliate'); ?></span>
                                    <?php else: ?>
                                        <span style="color: #999;">○ <?php _e('Pendiente', 'wcfm-product-affiliate'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="padding: 40px; text-align: center; background: #f5f5f5; border-radius: 8px;">
                    <span class="dashicons dashicons-info" style="font-size: 48px; color: #999;"></span>
                    <p style="color: #666; margin-top: 10px;">
                        <?php _e('No hay clics registrados en el período seleccionado', 'wcfm-product-affiliate'); ?>
                    </p>
                    <p style="color: #999;">
                        <?php _e('Comparte tus enlaces de productos afiliados para empezar a ver estadísticas aquí', 'wcfm-product-affiliate'); ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Separator -->
    <div class="wcfm-container" style="margin: 40px 0;">
        <hr style="border: none; border-top: 2px solid #e0e0e0;">
    </div>
    
    <!-- Statistics as Product Owner -->
    <div class="wcfm-container">
        <div class="wcfm-content">
            <h2 style="margin-top: 0; color: #00a32a;">
                <span class="dashicons dashicons-products" style="vertical-align: middle;"></span>
                <?php _e('Enlaces Compartidos de MIS Productos', 'wcfm-product-affiliate'); ?>
            </h2>
            <p style="color: #666; margin-bottom: 20px;">
                <?php _e('Aquí puedes ver quién está compartiendo tus productos y cuántas visitas están generando', 'wcfm-product-affiliate'); ?>
            </p>
        </div>
    </div>
    
    <!-- Owner Statistics Summary -->
    <?php if ($owner_stats && $owner_stats->total_clicks > 0): ?>
    <div class="wcfm-container">
        <div class="wcfm-content">
            <div class="wcfm-affiliate-stats-summary" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
                
                <!-- Total Clicks on My Products -->
                <div class="wcfm-stat-box" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #00a32a;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <h3 style="margin: 0; font-size: 32px; color: #00a32a;">
                                <?php echo number_format($owner_stats->total_clicks); ?>
                            </h3>
                            <p style="margin: 5px 0 0; color: #666;">
                                <?php _e('Clics Recibidos', 'wcfm-product-affiliate'); ?>
                            </p>
                        </div>
                        <span class="dashicons dashicons-chart-line" style="font-size: 48px; color: #00a32a; opacity: 0.3;"></span>
                    </div>
                </div>
                
                <!-- Affiliates Promoting -->
                <div class="wcfm-stat-box" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #2271b1;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <h3 style="margin: 0; font-size: 32px; color: #2271b1;">
                                <?php echo number_format($owner_stats->unique_affiliates); ?>
                            </h3>
                            <p style="margin: 5px 0 0; color: #666;">
                                <?php _e('Afiliados Activos', 'wcfm-product-affiliate'); ?>
                            </p>
                        </div>
                        <span class="dashicons dashicons-groups" style="font-size: 48px; color: #2271b1; opacity: 0.3;"></span>
                    </div>
                </div>
                
                <!-- Products Being Shared -->
                <div class="wcfm-stat-box" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #f0b849;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <h3 style="margin: 0; font-size: 32px; color: #f0b849;">
                                <?php echo number_format($owner_stats->unique_products); ?>
                            </h3>
                            <p style="margin: 5px 0 0; color: #666;">
                                <?php _e('Productos Compartidos', 'wcfm-product-affiliate'); ?>
                            </p>
                        </div>
                        <span class="dashicons dashicons-products" style="font-size: 48px; color: #f0b849; opacity: 0.3;"></span>
                    </div>
                </div>
                
                <!-- Conversions from Affiliates -->
                <div class="wcfm-stat-box" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #d63638;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <h3 style="margin: 0; font-size: 32px; color: #d63638;">
                                <?php echo number_format($owner_stats->total_conversions); ?>
                            </h3>
                            <p style="margin: 5px 0 0; color: #666;">
                                <?php _e('Ventas Generadas', 'wcfm-product-affiliate'); ?>
                            </p>
                        </div>
                        <span class="dashicons dashicons-cart" style="font-size: 48px; color: #d63638; opacity: 0.3;"></span>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
    
    <!-- Owner Clicks Table -->
    <div class="wcfm-container">
        <div class="wcfm-content">
            <h3><?php _e('Enlaces Compartidos por Afiliados', 'wcfm-product-affiliate'); ?></h3>
            
            <table class="wcfm-table" style="width: 100%;">
                <thead>
                    <tr>
                        <th><?php _e('Fecha/Hora', 'wcfm-product-affiliate'); ?></th>
                        <th><?php _e('Mi Producto', 'wcfm-product-affiliate'); ?></th>
                        <th><?php _e('Compartido por', 'wcfm-product-affiliate'); ?></th>
                        <th><?php _e('Origen', 'wcfm-product-affiliate'); ?></th>
                        <th><?php _e('Estado', 'wcfm-product-affiliate'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($owner_clicks as $click): ?>
                        <tr>
                            <td>
                                <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($click->created_at)); ?>
                            </td>
                            <td>
                                <a href="<?php echo get_permalink($click->product_id); ?>" target="_blank">
                                    <?php echo get_the_title($click->product_id); ?>
                                </a>
                            </td>
                            <td>
                                <strong style="color: #2271b1;">
                                    <?php 
                                    $affiliate = get_userdata($click->affiliate_vendor_id);
                                    echo $affiliate ? esc_html($affiliate->display_name) : '-';
                                    ?>
                                </strong>
                            </td>
                            <td>
                                <?php 
                                if ($click->referrer_url) {
                                    $parsed = parse_url($click->referrer_url);
                                    echo esc_html($parsed['host'] ?? 'Directo');
                                } else {
                                    _e('Directo', 'wcfm-product-affiliate');
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ($click->converted_to_sale): ?>
                                    <span style="color: #00a32a;">✓ <?php _e('Venta', 'wcfm-product-affiliate'); ?></span>
                                <?php else: ?>
                                    <span style="color: #999;">○ <?php _e('Visita', 'wcfm-product-affiliate'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
    <div class="wcfm-container">
        <div class="wcfm-content">
            <div style="padding: 40px; text-align: center; background: #f5f5f5; border-radius: 8px;">
                <span class="dashicons dashicons-products" style="font-size: 48px; color: #999;"></span>
                <p style="color: #666; margin-top: 10px;">
                    <?php _e('Aún no hay afiliados compartiendo tus productos', 'wcfm-product-affiliate'); ?>
                </p>
                <p style="color: #999;">
                    <?php _e('Cuando otros vendedores añadan tus productos como afiliados y los compartan, verás las estadísticas aquí', 'wcfm-product-affiliate'); ?>
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- How to Share Links -->
    <div class="wcfm-container" style="margin-top: 30px;">
        <div class="wcfm-content">
            <h3><?php _e('Cómo Compartir Enlaces de Productos Afiliados', 'wcfm-product-affiliate'); ?></h3>
            <div style="background: #e7f3ff; padding: 20px; border-left: 4px solid #2271b1; border-radius: 4px;">
                <ol style="margin: 0; padding-left: 20px;">
                    <li><?php _e('Ve a tu catálogo de productos afiliados', 'wcfm-product-affiliate'); ?></li>
                    <li><?php _e('Cuando estés logueado, los enlaces de tus productos afiliados incluirán automáticamente tu referencia', 'wcfm-product-affiliate'); ?></li>
                    <li><?php _e('Copia y comparte ese enlace en redes sociales, email, WhatsApp, etc.', 'wcfm-product-affiliate'); ?></li>
                    <li><?php _e('Cada vez que alguien haga clic en el enlace, se registrará aquí', 'wcfm-product-affiliate'); ?></li>
                    <li><?php _e('Si realizan una compra, aparecerá como "Convertido"', 'wcfm-product-affiliate'); ?></li>
                </ol>
            </div>
        </div>
    </div>

</div>
<!-- FIN collapse wcfm-collapse -->

<script>
function wcfm_filter_stats() {
    var date_from = document.querySelector('input[name="date_from"]').value;
    var date_to = document.querySelector('input[name="date_to"]').value;
    
    // Construir URL correcta para WCFM
    var wcfm_base = window.location.origin + window.location.pathname;
    var new_url = wcfm_base + 'affiliate-link-stats/?date_from=' + date_from + '&date_to=' + date_to;
    
    window.location.href = new_url;
    return false;
}
</script>

<style>
.wcfm-table {
    border-collapse: collapse;
    background: #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.wcfm-table th {
    background: #f5f5f5;
    padding: 12px;
    text-align: left;
    font-weight: 600;
    border-bottom: 2px solid #ddd;
}

.wcfm-table td {
    padding: 12px;
    border-bottom: 1px solid #eee;
}

.wcfm-table tr:hover {
    background: #f9f9f9;
}
</style>
