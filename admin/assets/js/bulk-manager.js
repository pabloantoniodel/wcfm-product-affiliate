/**
 * Bulk Affiliate Manager JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';
    
    console.log('✅ Bulk Affiliate Manager JS cargado');
    console.log('wcfmAffiliateBulk:', wcfmAffiliateBulk);
    
    var selectedVendor = null;
    var selectedProducts = [];
    
    // ==========================================
    // BÚSQUEDA DE PRODUCTOS
    // ==========================================
    
    $('#search-products-btn').on('click', function() {
        var search = $('#product-search').val();
        searchProducts(search);
    });
    
    $('#product-search').on('keypress', function(e) {
        if (e.which === 13) {
            var search = $(this).val();
            searchProducts(search);
        }
    });
    
    function searchProducts(search) {
        console.log('🔍 Buscando productos:', search);
        console.log('AJAX URL:', wcfmAffiliateBulk.ajaxurl);
        
        $.ajax({
            url: wcfmAffiliateBulk.ajaxurl,
            type: 'POST',
            data: {
                action: 'wcfm_affiliate_search_products',
                nonce: wcfmAffiliateBulk.nonce,
                search: search
            },
            beforeSend: function() {
                console.log('📤 Enviando búsqueda...');
                $('#search-products-btn').prop('disabled', true).html('<span class="wcfm-spinner"></span>');
            },
            success: function(response) {
                console.log('📥 Respuesta recibida:', response);
                if (response.success) {
                    console.log('✅ Productos encontrados:', response.data.products.length);
                    displaySearchResults(response.data.products);
                } else {
                    console.error('❌ Error:', response.data.message);
                    alert(response.data.message || 'Error al buscar productos');
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Error AJAX:', error);
                console.error('Status:', status);
                console.error('Response:', xhr.responseText);
                alert('Error de conexión: ' + error);
            },
            complete: function() {
                console.log('✔️ Búsqueda completada');
                $('#search-products-btn').prop('disabled', false).text('Buscar');
            }
        });
    }
    
    function displaySearchResults(products) {
        var $tbody = $('#search-results-body');
        $tbody.empty();
        
        if (products.length === 0) {
            $tbody.append('<tr><td colspan="4" style="text-align:center;">No se encontraron productos</td></tr>');
        } else {
            products.forEach(function(product) {
                var $row = $('<tr>');
                $row.append('<td>' + product.name + '</td>');
                $row.append('<td>' + product.vendor + '</td>');
                $row.append('<td>' + product.price + '</td>');
                $row.append('<td><button type="button" class="button button-small add-to-pool" data-product-id="' + product.id + '">Añadir</button></td>');
                $tbody.append($row);
            });
        }
        
        $('#search-results').show();
    }
    
    // ==========================================
    // AÑADIR A POOL
    // ==========================================
    
    $(document).on('click', '.add-to-pool', function() {
        var productId = $(this).data('product-id');
        addToPool(productId);
    });
    
    function addToPool(productId) {
        $.ajax({
            url: wcfmAffiliateBulk.ajaxurl,
            type: 'POST',
            data: {
                action: 'wcfm_affiliate_add_to_pool',
                nonce: wcfmAffiliateBulk.nonce,
                product_id: productId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || wcfmAffiliateBulk.i18n.error);
                }
            }
        });
    }
    
    // ==========================================
    // SELECCIONAR TODOS
    // ==========================================
    
    $('#select-all-products').on('change', function() {
        $('.product-checkbox').prop('checked', $(this).is(':checked'));
    });
    
    // ==========================================
    // BORRAR SELECCIONADOS
    // ==========================================
    
    $('#delete-selected-btn').on('click', function() {
        var productIds = [];
        $('.product-checkbox:checked').each(function() {
            productIds.push($(this).val());
        });
        
        if (productIds.length === 0) {
            alert(wcfmAffiliateBulk.i18n.selectProducts);
            return;
        }
        
        if (!confirm('¿Estás seguro de querer borrar ' + productIds.length + ' producto(s)?')) {
            return;
        }
        
        removeFromPool(productIds);
    });
    
    $(document).on('click', '.remove-product', function() {
        var productId = $(this).data('product-id');
        if (confirm('¿Estás seguro de querer quitar este producto?')) {
            removeFromPool([productId]);
        }
    });
    
    function removeFromPool(productIds) {
        $.ajax({
            url: wcfmAffiliateBulk.ajaxurl,
            type: 'POST',
            data: {
                action: 'wcfm_affiliate_remove_from_pool',
                nonce: wcfmAffiliateBulk.nonce,
                product_ids: productIds
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || wcfmAffiliateBulk.i18n.error);
                }
            }
        });
    }
    
    // ==========================================
    // ENVIAR A AFILIADO
    // ==========================================
    
    $('#send-to-vendor-btn').on('click', function() {
        selectedProducts = [];
        $('.product-checkbox:checked').each(function() {
            selectedProducts.push($(this).val());
        });
        
        if (selectedProducts.length === 0) {
            alert(wcfmAffiliateBulk.i18n.selectProducts);
            return;
        }
        
        // Abrir modal de selección de vendedor
        openVendorModal();
    });
    
    function openVendorModal() {
        $('#vendor-select-modal').fadeIn(300);
        searchVendors('', 1);
    }
    
    // ==========================================
    // BÚSQUEDA DE VENDEDORES
    // ==========================================
    
    $('#search-vendors-btn').on('click', function() {
        var search = $('#vendor-search').val();
        searchVendors(search, 1);
    });
    
    $('#vendor-search').on('keypress', function(e) {
        if (e.which === 13) {
            var search = $(this).val();
            searchVendors(search, 1);
        }
    });
    
    function searchVendors(search, page) {
        $.ajax({
            url: wcfmAffiliateBulk.ajaxurl,
            type: 'POST',
            data: {
                action: 'wcfm_affiliate_search_vendors',
                nonce: wcfmAffiliateBulk.nonce,
                search: search,
                page: page
            },
            beforeSend: function() {
                $('#search-vendors-btn').prop('disabled', true).html('<span class="wcfm-spinner"></span>');
            },
            success: function(response) {
                if (response.success) {
                    displayVendorList(response.data);
                } else {
                    alert(response.data.message || wcfmAffiliateBulk.i18n.error);
                }
            },
            complete: function() {
                $('#search-vendors-btn').prop('disabled', false).html('Buscar');
            }
        });
    }
    
    function displayVendorList(data) {
        var $tbody = $('#vendors-list-body');
        $tbody.empty();
        
        if (data.vendors.length === 0) {
            $tbody.append('<tr><td colspan="4" style="text-align:center;">No se encontraron vendedores</td></tr>');
        } else {
            data.vendors.forEach(function(vendor) {
                var $row = $('<tr>');
                $row.append('<td><strong>' + vendor.name + '</strong></td>');
                $row.append('<td>' + vendor.email + '</td>');
                $row.append('<td>' + vendor.products + '</td>');
                $row.append('<td><button type="button" class="button button-primary button-small select-vendor" data-vendor-id="' + vendor.id + '" data-vendor-name="' + vendor.name + '">Seleccionar</button></td>');
                $tbody.append($row);
            });
        }
        
        // Paginación
        displayPagination(data);
    }
    
    function displayPagination(data) {
        var $pagination = $('.vendor-pagination');
        $pagination.empty();
        
        if (data.pages > 1) {
            if (data.current_page > 1) {
                $pagination.append('<button type="button" class="button vendor-page" data-page="' + (data.current_page - 1) + '">« Anterior</button>');
            }
            
            $pagination.append('<span style="margin: 0 10px;">Página ' + data.current_page + ' de ' + data.pages + '</span>');
            
            if (data.current_page < data.pages) {
                $pagination.append('<button type="button" class="button vendor-page" data-page="' + (data.current_page + 1) + '">Siguiente »</button>');
            }
        }
    }
    
    $(document).on('click', '.vendor-page', function() {
        var page = $(this).data('page');
        var search = $('#vendor-search').val();
        searchVendors(search, page);
    });
    
    // ==========================================
    // SELECCIONAR VENDEDOR
    // ==========================================
    
    $(document).on('click', '.select-vendor', function() {
        selectedVendor = {
            id: $(this).data('vendor-id'),
            name: $(this).data('vendor-name')
        };
        
        // Cerrar modal de vendedor
        $('#vendor-select-modal').fadeOut(300);
        
        // Abrir modal de confirmación
        openConfirmModal();
    });
    
    function openConfirmModal() {
        $('#selected-vendor-name').text(selectedVendor.name);
        
        // Listar productos seleccionados
        var $list = $('#products-to-affiliate-list');
        $list.empty();
        
        selectedProducts.forEach(function(productId) {
            var $row = $('tr[data-product-id="' + productId + '"]');
            var productName = $row.find('td:eq(2)').text().trim();
            
            var $li = $('<li>');
            $li.append('<input type="checkbox" class="product-affiliate-checkbox" value="' + productId + '" checked> ');
            $li.append('<span>' + productName + '</span>');
            $list.append($li);
        });
        
        $('#confirm-affiliate-modal').fadeIn(300);
    }
    
    // ==========================================
    // CONFIRMAR AFILIACIÓN
    // ==========================================
    
    $('#confirm-affiliate-btn').on('click', function() {
        var finalProducts = [];
        $('.product-affiliate-checkbox:checked').each(function() {
            finalProducts.push($(this).val());
        });
        
        if (finalProducts.length === 0) {
            alert(wcfmAffiliateBulk.i18n.selectProducts);
            return;
        }
        
        if (!selectedVendor) {
            alert(wcfmAffiliateBulk.i18n.selectVendor);
            return;
        }
        
        bulkAffiliate(finalProducts, selectedVendor.id);
    });
    
    function bulkAffiliate(productIds, vendorId) {
        $.ajax({
            url: wcfmAffiliateBulk.ajaxurl,
            type: 'POST',
            data: {
                action: 'wcfm_affiliate_bulk_affiliate',
                nonce: wcfmAffiliateBulk.nonce,
                product_ids: productIds,
                vendor_id: vendorId
            },
            beforeSend: function() {
                $('#confirm-affiliate-btn').prop('disabled', true).html('<span class="wcfm-spinner"></span> Procesando...');
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message || wcfmAffiliateBulk.i18n.success);
                    location.reload();
                } else {
                    alert(response.data.message || wcfmAffiliateBulk.i18n.error);
                }
            },
            complete: function() {
                $('#confirm-affiliate-btn').prop('disabled', false).html('Aceptar y Afiliar');
            }
        });
    }
    
    // ==========================================
    // CERRAR MODALES
    // ==========================================
    
    $('.wcfm-modal-close, #cancel-vendor-btn, #cancel-affiliate-btn').on('click', function() {
        $('.wcfm-modal').fadeOut(300);
    });
    
    // Cerrar modal al hacer click fuera
    $('.wcfm-modal').on('click', function(e) {
        if ($(e.target).hasClass('wcfm-modal')) {
            $(this).fadeOut(300);
        }
    });
});

