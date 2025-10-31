jQuery(document).ready(function($) {
    'use strict';
    
    // Añadir producto afiliado
    $(document).on('click', '.wcfm_affiliate_add_button', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var productId = $button.data('product-id');
        var originalText = $button.html();
        
        if (!confirm(wcfm_affiliate_params.i18n.confirm_add)) {
            return;
        }
        
        $button.prop('disabled', true).html('<span class="wcfmfa fa-spinner fa-spin"></span> Añadiendo...');
        
        $.ajax({
            url: wcfm_affiliate_params.ajax_url,
            type: 'POST',
            data: {
                action: 'wcfm_affiliate_add_product',
                nonce: wcfm_affiliate_params.nonce,
                product_id: productId
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message || wcfm_affiliate_params.i18n.success_add);
                    location.reload();
                } else {
                    alert(response.data.message || wcfm_affiliate_params.i18n.error);
                    $button.prop('disabled', false).html(originalText);
                }
            },
            error: function() {
                alert(wcfm_affiliate_params.i18n.error);
                $button.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Eliminar producto afiliado
    $(document).on('click', '.wcfm_affiliate_remove_button', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var productId = $button.data('product-id');
        var originalText = $button.html();
        
        if (!confirm(wcfm_affiliate_params.i18n.confirm_remove)) {
            return;
        }
        
        $button.prop('disabled', true).html('<span class="wcfmfa fa-spinner fa-spin"></span> Eliminando...');
        
        $.ajax({
            url: wcfm_affiliate_params.ajax_url,
            type: 'POST',
            data: {
                action: 'wcfm_affiliate_remove_product',
                nonce: wcfm_affiliate_params.nonce,
                product_id: productId
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message || wcfm_affiliate_params.i18n.success_remove);
                    location.reload();
                } else {
                    alert(response.data.message || wcfm_affiliate_params.i18n.error);
                    $button.prop('disabled', false).html(originalText);
                }
            },
            error: function() {
                alert(wcfm_affiliate_params.i18n.error);
                $button.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Añadir productos masivamente
    $(document).on('click', '#wcfm_bulk_add_affiliates', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var productIds = [];
        var originalText = $button.html();
        
        $('.affiliate-product-checkbox:checked').each(function() {
            productIds.push($(this).val());
        });
        
        if (productIds.length === 0) {
            alert(wcfm_affiliate_params.i18n.select_products || 'Por favor selecciona al menos un producto');
            return;
        }
        
        if (!confirm('¿Añadir ' + productIds.length + ' producto(s) a tu tienda?')) {
            return;
        }
        
        $button.prop('disabled', true).html('<span class="wcfmfa fa-spinner fa-spin"></span> Añadiendo ' + productIds.length + ' productos...');
        
        $.ajax({
            url: wcfm_affiliate_params.ajax_url,
            type: 'POST',
            data: {
                action: 'wcfm_affiliate_bulk_add',
                nonce: wcfm_affiliate_params.nonce,
                product_ids: productIds
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message || wcfm_affiliate_params.i18n.error);
                    $button.prop('disabled', false).html(originalText);
                }
            },
            error: function() {
                alert(wcfm_affiliate_params.i18n.error);
                $button.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Seleccionar/deseleccionar todos
    $(document).on('change', '#select-all-affiliates', function() {
        var isChecked = $(this).prop('checked');
        $('.affiliate-product-checkbox').prop('checked', isChecked);
        updateBulkButton();
    });
    
    // Actualizar botón masivo al cambiar selección
    $(document).on('change', '.affiliate-product-checkbox', function() {
        updateBulkButton();
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
    
    // Limpiar búsqueda
    $(document).on('click', '#affiliate_clear_search', function(e) {
        e.preventDefault();
        window.location.href = window.location.href.split('?')[0];
    });
    
    // Paginación
    $(document).on('click', '.affiliate-pagination', function(e) {
        e.preventDefault();
        var page = $(this).data('page');
        window.location.href = buildSearchUrl(page);
    });
    
    // Búsqueda automática al escribir (3+ caracteres) - recarga la página
    var searchTimeout = null;
    $(document).on('keyup', '#affiliate_search', function() {
        var searchTerm = $(this).val().trim();
        var $status = $('#products-search-status');
        
        // Limpiar timeout anterior
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }
        
        // Si hay menos de 3 caracteres, mostrar mensaje
        if (searchTerm.length > 0 && searchTerm.length < 3) {
            $status.html('⏳ Escribe al menos 3 caracteres para buscar...').css('color', '#999');
            return;
        }
        
        // Si está vacío, recargar página sin filtros (mostrar todo)
        if (searchTerm.length === 0) {
            $status.html('');
            searchTimeout = setTimeout(function() {
                sessionStorage.setItem('affiliate_scroll_to_results', '1');
                window.location.href = window.location.href.split('?')[0];
            }, 500);
            return;
        }
        
        // Si hay 3+ caracteres, esperar 500ms y recargar con búsqueda
        if (searchTerm.length >= 3) {
            $status.html('⏳ Buscando...').css('color', '#2271b1');
            
            searchTimeout = setTimeout(function() {
                // Después de recargar, hacer scroll a la sección de productos
                sessionStorage.setItem('affiliate_scroll_to_results', '1');
                window.location.href = buildSearchUrl(1);
            }, 500);
        }
    });
    
    // Restaurar scroll después de recarga
    if (sessionStorage.getItem('affiliate_scroll_to_results')) {
        sessionStorage.removeItem('affiliate_scroll_to_results');
        setTimeout(function() {
            var resultsSection = $('#productos-disponibles-section');
            if (resultsSection.length) {
                $('html, body').animate({
                    scrollTop: resultsSection.offset().top - 50
                }, 400);
            }
        }, 300);
    }
});
