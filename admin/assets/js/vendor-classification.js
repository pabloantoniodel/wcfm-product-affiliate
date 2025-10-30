/**
 * JavaScript para Clasificación de Vendedores
 * @package WCFM_Product_Affiliate
 * @since 1.3.0
 */

(function($) {
    'use strict';
    
    let currentPage = 1;
    let searchTimeout = null;
    let totalVendors = 0;
    
    $(document).ready(function() {
        console.log('✅ WCFM Vendor Classification: JavaScript cargado');
        
        // Cargar vendedores inicialmente
        loadVendors();
        
        /**
         * Búsqueda en tiempo real
         */
        $('#vendor-search').on('input', function() {
            const searchTerm = $(this).val().trim();
            
            // Mostrar/ocultar botón limpiar
            if (searchTerm.length > 0) {
                $('#clear-search').show();
            } else {
                $('#clear-search').hide();
            }
            
            // Debounce de 500ms
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                currentPage = 1;
                loadVendors(searchTerm);
            }, 500);
        });
        
        /**
         * Limpiar búsqueda
         */
        $('#clear-search').on('click', function() {
            $('#vendor-search').val('');
            $(this).hide();
            currentPage = 1;
            loadVendors();
        });
        
        /**
         * Cambio en checkboxes
         */
        $(document).on('change', '.comercio-checkbox, .comercial-checkbox', function() {
            const $row = $(this).closest('tr');
            const $saveBtn = $row.find('.save-classification-btn');
            
            // Habilitar botón de guardar
            $saveBtn.prop('disabled', false);
            
            console.log('📝 Checkbox cambiado - Vendor:', $row.data('vendor-id'));
        });
        
        /**
         * Guardar clasificación
         */
        $(document).on('click', '.save-classification-btn', function() {
            const $btn = $(this);
            const $row = $btn.closest('tr');
            const vendorId = $row.data('vendor-id');
            const $comercioCheckbox = $row.find('.comercio-checkbox');
            const $comercialCheckbox = $row.find('.comercial-checkbox');
            const $status = $row.find('.save-status');
            
            const isComercio = $comercioCheckbox.is(':checked');
            const isComercial = $comercialCheckbox.is(':checked');
            
            console.log('💾 Guardando clasificación - Vendor:', vendorId, '- Comercio:', isComercio, '- Comercial:', isComercial);
            
            // Deshabilitar botón y cambiar texto
            $btn.prop('disabled', true);
            $btn.find('span').text('Guardando...');
            $btn.find('i').removeClass('fa-save').addClass('fa-spinner fa-spin');
            
            // Ocultar mensaje anterior
            $status.removeClass('show success error');
            
            // Enviar AJAX
            $.ajax({
                url: wcfmVendorClassification.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcfm_update_vendor_classification',
                    nonce: wcfmVendorClassification.nonce,
                    vendor_id: vendorId,
                    is_comercio: isComercio,
                    is_comercial: isComercial
                },
                success: function(response) {
                    console.log('✅ Respuesta del servidor:', response);
                    
                    if (response.success) {
                        // Mostrar mensaje de éxito
                        $status
                            .addClass('success show')
                            .html('<i class="fas fa-check-circle"></i> ' + response.data.message);
                        
                        // Ocultar mensaje después de 3 segundos
                        setTimeout(function() {
                            $status.removeClass('show');
                        }, 3000);
                        
                    } else {
                        // Mostrar mensaje de error
                        $status
                            .addClass('error show')
                            .html('<i class="fas fa-exclamation-circle"></i> ' + response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('❌ Error en AJAX:', {xhr, status, error});
                    
                    $status
                        .addClass('error show')
                        .html('<i class="fas fa-exclamation-circle"></i> Error de conexión');
                },
                complete: function() {
                    // Restaurar botón
                    $btn.prop('disabled', true); // Mantener deshabilitado hasta nuevo cambio
                    $btn.find('span').text('Guardar');
                    $btn.find('i').removeClass('fa-spinner fa-spin').addClass('fa-save');
                }
            });
        });
        
        /**
         * Paginación
         */
        $(document).on('click', '.pagination-btn', function() {
            if ($(this).prop('disabled') || $(this).hasClass('active')) {
                return;
            }
            
            const page = $(this).data('page');
            if (page) {
                currentPage = page;
                const searchTerm = $('#vendor-search').val().trim();
                loadVendors(searchTerm);
            }
        });
        
    });
    
    /**
     * Cargar vendedores
     */
    function loadVendors(search = '') {
        console.log('🔄 Cargando vendedores - Búsqueda:', search || '(sin filtro)', '- Página:', currentPage);
        
        const $vendorsList = $('#vendors-list');
        const $pagination = $('#classification-pagination');
        const $resultsCount = $('#search-results-count');
        
        // Mostrar loading
        $vendorsList.html(`
            <tr>
                <td colspan="5" class="loading-row">
                    <i class="fas fa-spinner fa-spin"></i>
                    Cargando vendedores...
                </td>
            </tr>
        `);
        
        // AJAX
        $.ajax({
            url: wcfmVendorClassification.ajax_url,
            type: 'POST',
            data: {
                action: 'wcfm_search_vendors_classification',
                nonce: wcfmVendorClassification.nonce,
                search: search,
                page: currentPage
            },
            success: function(response) {
                console.log('✅ Vendedores cargados:', response);
                
                if (response.success) {
                    const data = response.data;
                    totalVendors = data.total;
                    
                    // Actualizar contador
                    if (search) {
                        $resultsCount.html(`Se encontraron <strong>${data.total}</strong> vendedor(es) con "<strong>${search}</strong>"`);
                    } else {
                        $resultsCount.html(`Total: <strong>${data.total}</strong> vendedores`);
                    }
                    
                    // Renderizar vendedores
                    if (data.vendors.length > 0) {
                        displayVendors(data.vendors);
                        displayPagination(data.pages, data.current_page, data.total, data.per_page);
                    } else {
                        $vendorsList.html(`
                            <tr class="no-results-row">
                                <td colspan="5">
                                    <i class="fas fa-search"></i>
                                    <div>No se encontraron vendedores con los criterios de búsqueda.</div>
                                </td>
                            </tr>
                        `);
                        $pagination.hide();
                    }
                } else {
                    console.error('❌ Error en respuesta:', response);
                    $vendorsList.html(`
                        <tr class="no-results-row">
                            <td colspan="5">
                                <i class="fas fa-exclamation-triangle"></i>
                                <div>Error al cargar vendedores. Por favor, inténtalo de nuevo.</div>
                            </td>
                        </tr>
                    `);
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Error en AJAX:', {xhr, status, error});
                $vendorsList.html(`
                    <tr class="no-results-row">
                        <td colspan="5">
                            <i class="fas fa-times-circle"></i>
                            <div>Error de conexión. Por favor, recarga la página.</div>
                        </td>
                    </tr>
                `);
            }
        });
    }
    
    /**
     * Mostrar vendedores en la tabla
     */
    function displayVendors(vendors) {
        const $vendorsList = $('#vendors-list');
        let html = '';
        
        vendors.forEach(function(vendor) {
            html += `
                <tr data-vendor-id="${vendor.id}">
                    <td class="vendor-column">
                        <div class="vendor-info">
                            <span class="vendor-name">${escapeHtml(vendor.full_name)}</span>
                            <span class="vendor-login">@${escapeHtml(vendor.user_login)}</span>
                        </div>
                    </td>
                    <td class="email-column">
                        <a href="mailto:${escapeHtml(vendor.email)}" class="vendor-email">
                            ${escapeHtml(vendor.email)}
                        </a>
                    </td>
                    <td class="comercio-column">
                        <div class="classification-checkbox">
                            <input 
                                type="checkbox" 
                                class="comercio-checkbox" 
                                ${vendor.is_comercio ? 'checked' : ''}
                            >
                        </div>
                    </td>
                    <td class="comercial-column">
                        <div class="classification-checkbox">
                            <input 
                                type="checkbox" 
                                class="comercial-checkbox" 
                                ${vendor.is_comercial ? 'checked' : ''}
                            >
                        </div>
                    </td>
                    <td class="actions-column">
                        <button type="button" class="save-classification-btn" disabled>
                            <i class="fas fa-save"></i>
                            <span>Guardar</span>
                        </button>
                        <span class="save-status"></span>
                    </td>
                </tr>
            `;
        });
        
        $vendorsList.html(html);
    }
    
    /**
     * Mostrar paginación
     */
    function displayPagination(totalPages, currentPage, total, perPage) {
        const $pagination = $('#classification-pagination');
        
        if (totalPages <= 1) {
            $pagination.hide();
            return;
        }
        
        let html = '<div class="pagination-info">';
        const start = ((currentPage - 1) * perPage) + 1;
        const end = Math.min(currentPage * perPage, total);
        html += `Mostrando ${start} - ${end} de ${total} vendedores`;
        html += '</div>';
        
        html += '<div class="pagination-buttons">';
        
        // Botón anterior
        html += `<button class="pagination-btn" data-page="${currentPage - 1}" ${currentPage === 1 ? 'disabled' : ''}>
            <i class="fas fa-chevron-left"></i> Anterior
        </button>`;
        
        // Números de página
        const maxButtons = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxButtons / 2));
        let endPage = Math.min(totalPages, startPage + maxButtons - 1);
        
        if (endPage - startPage < maxButtons - 1) {
            startPage = Math.max(1, endPage - maxButtons + 1);
        }
        
        for (let i = startPage; i <= endPage; i++) {
            html += `<button class="pagination-btn ${i === currentPage ? 'active' : ''}" data-page="${i}">
                ${i}
            </button>`;
        }
        
        // Botón siguiente
        html += `<button class="pagination-btn" data-page="${currentPage + 1}" ${currentPage === totalPages ? 'disabled' : ''}>
            Siguiente <i class="fas fa-chevron-right"></i>
        </button>`;
        
        html += '</div>';
        
        $pagination.html(html).show();
    }
    
    /**
     * Escapar HTML
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
})(jQuery);

