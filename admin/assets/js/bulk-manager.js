/**
 * Bulk Affiliate Manager JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';
    
    console.log('✅ Bulk Affiliate Manager JS cargado');
    console.log('wcfmAffiliateBulk:', wcfmAffiliateBulk);
    
    var selectedVendor = null;
    var selectedProducts = [];
    
    // Marcar "Seleccionar Todos" si todos están marcados al cargar
    function updateSelectAllCheckbox() {
        var totalCheckboxes = $('.product-checkbox').length;
        var checkedCheckboxes = $('.product-checkbox:checked').length;
        $('#select-all-products').prop('checked', totalCheckboxes > 0 && totalCheckboxes === checkedCheckboxes);
    }
    
    // Ejecutar al cargar
    updateSelectAllCheckbox();
    
    // ==========================================
    // BÚSQUEDA DE PRODUCTOS
    // ==========================================
    
    var currentSearchTerm = '';
    
    $('#search-products-btn').on('click', function() {
        currentSearchTerm = $('#product-search').val();
        searchProducts(currentSearchTerm, 1);
    });
    
    $('#product-search').on('keypress', function(e) {
        if (e.which === 13) {
            currentSearchTerm = $(this).val();
            searchProducts(currentSearchTerm, 1);
        }
    });
    
    function searchProducts(search, page) {
        console.log('🔍 Buscando productos:', search, 'Página:', page);
        console.log('AJAX URL:', wcfmAffiliateBulk.ajaxurl);
        
        $.ajax({
            url: wcfmAffiliateBulk.ajaxurl,
            type: 'POST',
            data: {
                action: 'wcfm_affiliate_search_products',
                nonce: wcfmAffiliateBulk.nonce,
                search: search,
                page: page
            },
            beforeSend: function() {
                console.log('📤 Enviando búsqueda...');
                $('#search-products-btn').prop('disabled', true).html('<span class="wcfm-spinner"></span>');
            },
            success: function(response) {
                console.log('📥 Respuesta recibida:', response);
                if (response.success) {
                    console.log('✅ Productos encontrados:', response.data.products.length);
                    displaySearchResults(response.data);
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
    
    function displaySearchResults(data) {
        var products = data.products;
        var $tbody = $('#search-results-body');
        $tbody.empty();
        
        // Actualizar contador
        $('#search-results-count').text('(' + data.total + ' encontrados)');
        
        if (products.length === 0) {
            $tbody.append('<tr><td colspan="6" style="text-align:center;">No se encontraron productos</td></tr>');
        } else {
            products.forEach(function(product) {
                var $row = $('<tr>');
                $row.append('<td><input type="checkbox" class="search-product-checkbox" value="' + product.id + '" /></td>');
                $row.append('<td>' + product.image + '</td>');
                $row.append('<td>' + product.name + '</td>');
                $row.append('<td>' + product.vendor + '</td>');
                $row.append('<td>' + product.price + '</td>');
                $row.append('<td><button type="button" class="button button-small add-to-pool" data-product-id="' + product.id + '">Añadir</button></td>');
                $tbody.append($row);
            });
        }
        
        // Mostrar paginación
        displaySearchPagination(data);
        
        $('#search-results').show();
    }
    
    function displaySearchPagination(data) {
        var $pagination = $('.search-pagination');
        $pagination.empty();
        
        if (data.pages > 1) {
            if (data.current_page > 1) {
                $pagination.append('<button type="button" class="button search-page" data-page="' + (data.current_page - 1) + '">« Anterior</button> ');
            }
            
            $pagination.append('<span style="margin: 0 10px;">Página ' + data.current_page + ' de ' + data.pages + '</span>');
            
            if (data.current_page < data.pages) {
                $pagination.append(' <button type="button" class="button search-page" data-page="' + (data.current_page + 1) + '">Siguiente »</button>');
            }
        }
    }
    
    $(document).on('click', '.search-page', function() {
        var page = $(this).data('page');
        searchProducts(currentSearchTerm, page);
    });
    
    // ==========================================
    // SELECCIONAR TODOS EN BÚSQUEDA
    // ==========================================
    
    $('#select-all-search').on('change', function() {
        $('.search-product-checkbox').prop('checked', $(this).is(':checked'));
    });
    
    // ==========================================
    // AÑADIR SELECCIONADOS DE BÚSQUEDA
    // ==========================================
    
    $('#add-selected-search-btn').on('click', function() {
        var productIds = [];
        $('.search-product-checkbox:checked').each(function() {
            productIds.push($(this).val());
        });
        
        if (productIds.length === 0) {
            alert('Por favor selecciona al menos un producto');
            return;
        }
        
        addMultipleToPool(productIds);
    });
    
    // ==========================================
    // AÑADIR A POOL
    // ==========================================
    
    $(document).on('click', '.add-to-pool', function() {
        var productId = $(this).data('product-id');
        addToPool([productId]);
    });
    
    function addToPool(productIds) {
        addMultipleToPool(productIds);
    }
    
    function addMultipleToPool(productIds) {
        var successCount = 0;
        var totalProducts = productIds.length;
        var errors = [];
        
        function addNext(index) {
            if (index >= productIds.length) {
                // Terminado - mostrar resultado y recargar
                var message = successCount + ' de ' + totalProducts + ' productos añadidos';
                if (errors.length > 0) {
                    message += '\n\nErrores:\n' + errors.join('\n');
                }
                alert(message);
                location.reload();
                return;
            }
            
            var productId = productIds[index];
            
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
                        successCount++;
                    } else {
                        errors.push('Producto ' + productId + ': ' + response.data.message);
                    }
                },
                error: function() {
                    errors.push('Producto ' + productId + ': Error de conexión');
                },
                complete: function() {
                    addNext(index + 1);
                }
            });
        }
        
        addNext(0);
    }
    
    // ==========================================
    // SELECCIONAR TODOS
    // ==========================================
    
    $('#select-all-products').on('change', function() {
        $('.product-checkbox').prop('checked', $(this).is(':checked'));
    });
    
    // Actualizar "Seleccionar Todos" cuando se marque/desmarque un checkbox individual
    $(document).on('change', '.product-checkbox', function() {
        updateSelectAllCheckbox();
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
    
    // Filtros de clasificación - actualizar búsqueda al cambiar
    $('.classification-filter').on('change', function() {
        var filterComercio = $('#filter-comercio').is(':checked');
        var filterComercial = $('#filter-comercial').is(':checked');
        
        console.log('🔄 Filtro cambiado - Comercio:', filterComercio, '- Comercial:', filterComercial);
        
        // Buscar automáticamente con los nuevos filtros
        var search = $('#vendor-search').val();
        searchVendors(search, 1);
    });
    
    function searchVendors(search, page) {
        // Obtener filtros de clasificación
        var filterComercio = $('#filter-comercio').is(':checked');
        var filterComercial = $('#filter-comercial').is(':checked');
        
        console.log('🔍 Búsqueda vendors - Search:', search, '- Comercio:', filterComercio, '- Comercial:', filterComercial);
        
        $.ajax({
            url: wcfmAffiliateBulk.ajaxurl,
            type: 'POST',
            data: {
                action: 'wcfm_affiliate_search_vendors',
                nonce: wcfmAffiliateBulk.nonce,
                search: search,
                page: page,
                filter_comercio: filterComercio,
                filter_comercial: filterComercial
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
            $tbody.append('<tr><td colspan="5" style="text-align:center;">No se encontraron vendedores</td></tr>');
        } else {
            data.vendors.forEach(function(vendor) {
                var $row = $('<tr>');
                $row.append('<td class="check-column"><input type="checkbox" class="vendor-checkbox" value="' + vendor.id + '" data-vendor-name="' + vendor.name + '" /></td>');
                $row.append('<td><strong>' + vendor.name + '</strong></td>');
                $row.append('<td>' + vendor.email + '</td>');
                $row.append('<td>' + vendor.products + '</td>');
                $row.append('<td>' + vendor.registered + '</td>');
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
    // SELECCIONAR TODOS VENDEDORES
    // ==========================================
    
    $('#select-all-vendors').on('change', function() {
        $('.vendor-checkbox').prop('checked', $(this).is(':checked'));
    });
    
    // ==========================================
    // AFILIAR A VENDEDORES SELECCIONADOS
    // ==========================================
    
    $('#affiliate-to-selected-vendors-btn').on('click', function() {
        var selectedVendorIds = [];
        var selectedVendorNames = [];
        
        $('.vendor-checkbox:checked').each(function() {
            selectedVendorIds.push($(this).val());
            selectedVendorNames.push($(this).data('vendor-name'));
        });
        
        if (selectedVendorIds.length === 0) {
            alert('Por favor selecciona al menos un vendedor');
            return;
        }
        
        // Confirmar afiliación múltiple
        var confirm_msg = '¿Afiliar ' + selectedProducts.length + ' producto(s) a ' + selectedVendorIds.length + ' vendedor(es)?\\n\\n';
        confirm_msg += 'Vendedores: ' + selectedVendorNames.join(', ');
        
        if (!confirm(confirm_msg)) {
            return;
        }
        
        // Cerrar modal de vendedores
        $('#vendor-select-modal').fadeOut(300);
        
        // Afiliar a cada vendedor
        affiliateToMultipleVendors(selectedVendorIds);
    });
    
    function affiliateToMultipleVendors(vendorIds) {
        var totalVendors = vendorIds.length;
        var completedVendors = 0;
        var successCount = 0;
        var allErrors = [];
        
        function affiliateNext(index) {
            if (index >= vendorIds.length) {
                // Terminado
                var message = 'Afiliación completada:\\n';
                message += successCount + ' de ' + totalVendors + ' vendedores procesados correctamente';
                
                if (allErrors.length > 0) {
                    message += '\\n\\nErrores:\\n' + allErrors.join('\\n');
                }
                
                alert(message);
                location.reload();
                return;
            }
            
            var vendorId = vendorIds[index];
            
            bulkAffiliate(selectedProducts, vendorId, function(success) {
                if (success) {
                    successCount++;
                } else {
                    allErrors.push('Vendedor ID ' + vendorId + ': Error');
                }
                affiliateNext(index + 1);
            });
        }
        
        affiliateNext(0);
    }
    
    // ==========================================
    // SELECCIONAR VENDEDOR (método antiguo individual)
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
        console.log('🔘 Click en Confirmar Afiliación');
        
        var finalProducts = [];
        $('.product-affiliate-checkbox:checked').each(function() {
            finalProducts.push($(this).val());
        });
        
        console.log('Productos finales seleccionados:', finalProducts);
        console.log('Vendedor seleccionado:', selectedVendor);
        
        if (finalProducts.length === 0) {
            console.warn('⚠️ No hay productos seleccionados');
            alert('Por favor selecciona al menos un producto');
            return;
        }
        
        if (!selectedVendor) {
            console.warn('⚠️ No hay vendedor seleccionado');
            alert('Por favor selecciona un vendedor');
            return;
        }
        
        console.log('✅ Validaciones OK, llamando a bulkAffiliate');
        bulkAffiliate(finalProducts, selectedVendor.id);
    });
    
    function bulkAffiliate(productIds, vendorId, callback) {
        console.log('🚀 Iniciando afiliación masiva');
        console.log('Product IDs:', productIds);
        console.log('Vendor ID:', vendorId);
        
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
                console.log('📤 Enviando afiliación...');
                if (!callback) {
                    $('#confirm-affiliate-btn').prop('disabled', true).html('<span class="wcfm-spinner"></span> Procesando...');
                }
            },
            success: function(response) {
                console.log('📥 Respuesta afiliación:', response);
                if (callback) {
                    // Modo callback (para afiliación múltiple)
                    callback(response.success);
                } else {
                    // Modo normal (un solo vendedor)
                    if (response.success) {
                        alert(response.data.message || 'Productos afiliados correctamente');
                        location.reload();
                    } else {
                        alert(response.data.message || 'Error al afiliar productos');
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Error AJAX afiliación:', error);
                console.error('Status:', status);
                console.error('Response:', xhr.responseText);
                if (callback) {
                    callback(false);
                } else {
                    alert('Error de conexión al afiliar: ' + error);
                }
            },
            complete: function() {
                console.log('✔️ Afiliación completada');
                if (!callback) {
                    $('#confirm-affiliate-btn').prop('disabled', false).html('Aceptar y Afiliar');
                }
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

