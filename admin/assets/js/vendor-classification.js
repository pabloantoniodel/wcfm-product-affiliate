/**
 * JavaScript para Clasificación de Clientes
 * @package WCFM_Product_Affiliate
 * @since 1.3.0
 */

(function($) {
    'use strict';
    
    let currentPage = 1;
    let searchTimeout = null;
    let totalCustomers = 0;
    
    $(document).ready(function() {
        console.log('✅ WCFM Customer Classification: JavaScript cargado');
        
        // Cargar clientes inicialmente
        loadCustomers();
        
        /**
         * Búsqueda en tiempo real - Solo buscar con 3+ caracteres o si está vacío
         */
        $('#customer-search').on('input', function() {
            const searchTerm = $(this).val().trim();
            const $resultsCount = $('#search-results-count');
            
            // Mostrar/ocultar botón limpiar
            if (searchTerm.length > 0) {
                $('#clear-search').show();
            } else {
                $('#clear-search').hide();
            }
            
            // Solo buscar si tiene 3+ caracteres o si está vacío (para mostrar todos)
            if (searchTerm.length === 0) {
                // Si está vacío, buscar todos
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    currentPage = 1;
                    loadCustomers();
                }, 300);
            } else if (searchTerm.length >= 3) {
                // Si tiene 3+ caracteres, buscar con debounce
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    currentPage = 1;
                    loadCustomers();
                }, 500);
            } else {
                // Si tiene menos de 3 caracteres, mostrar mensaje
                $resultsCount.html(`<span style="color: #8c8f94;">Escribe al menos 3 caracteres para buscar...</span>`);
            }
        });
        
        /**
         * Limpiar búsqueda
         */
        $('#clear-search').on('click', function() {
            $('#customer-search').val('');
            $(this).hide();
            currentPage = 1;
            loadCustomers();
        });
        
        /**
         * Cambio en filtros de checkboxes - Buscar inmediatamente
         */
        $(document).on('change', '.filter-checkbox', function() {
            currentPage = 1;
            // Buscar inmediatamente sin debounce
            clearTimeout(searchTimeout);
            loadCustomers();
        });
        
        /**
         * Cambio en selector de orden - Buscar inmediatamente
         */
        $('#order-by').on('change', function() {
            currentPage = 1;
            // Buscar inmediatamente sin debounce
            clearTimeout(searchTimeout);
            loadCustomers();
        });
        
        /**
         * Cambio en checkboxes de clasificación
         */
        $(document).on('change', '.classification-checkbox input[type="checkbox"]', function() {
            const $row = $(this).closest('tr');
            const $saveBtn = $row.find('.save-classification-btn');
            
            // Habilitar botón de guardar
            $saveBtn.prop('disabled', false);
            
            console.log('📝 Checkbox cambiado - Customer:', $row.data('customer-id'));
        });
        
        /**
         * Guardar clasificación
         */
        $(document).on('click', '.save-classification-btn', function() {
            const $btn = $(this);
            const $row = $btn.closest('tr');
            const customerId = $row.data('customer-id');
            const $status = $row.find('.save-status');
            
            const revisado = $row.find('.revisado-checkbox').is(':checked');
            const contrato = $row.find('.contrato-checkbox').is(':checked');
            const interesa = $row.find('.interesa-checkbox').is(':checked');
            const enEspera = $row.find('.en_espera-checkbox').is(':checked');
            const noInteresa = $row.find('.no_interesa-checkbox').is(':checked');
            const comercio = $row.find('.comercio-checkbox').is(':checked');
            const comercial = $row.find('.comercial-checkbox').is(':checked');
            
            console.log('💾 Guardando clasificación - Customer:', customerId);
            
            // Deshabilitar botón y cambiar texto
            $btn.prop('disabled', true);
            $btn.find('span').text('Guardando...');
            $btn.find('i').removeClass('fa-save').addClass('fa-spinner fa-spin');
            
            // Ocultar mensaje anterior
            $status.removeClass('show success error');
            
            // Enviar AJAX
            const ajaxData = {
                action: 'wcfm_update_customer_classification',
                nonce: wcfmVendorClassification.nonce,
                customer_id: customerId,
                revisado: revisado ? 'true' : 'false',
                contrato: contrato ? 'true' : 'false',
                interesa: interesa ? 'true' : 'false',
                en_espera: enEspera ? 'true' : 'false',
                no_interesa: noInteresa ? 'true' : 'false',
                comercio: comercio ? 'true' : 'false',
                comercial: comercial ? 'true' : 'false'
            };
            
            console.log('📤 Enviando AJAX:', ajaxData);
            
            $.ajax({
                url: wcfmVendorClassification.ajax_url,
                type: 'POST',
                data: ajaxData,
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
                loadCustomers();
            }
        });
        
    });
    
    /**
     * Cargar clientes
     */
    function loadCustomers() {
        const searchTerm = $('#customer-search').val().trim();
        const filterRevisado = $('.filter-checkbox[value="revisado"]').is(':checked');
        const filterContrato = $('.filter-checkbox[value="contrato"]').is(':checked');
        const filterInteresa = $('.filter-checkbox[value="interesa"]').is(':checked');
        const filterEnEspera = $('.filter-checkbox[value="en_espera"]').is(':checked');
        const filterNoInteresa = $('.filter-checkbox[value="no_interesa"]').is(':checked');
        const filterComercial = $('.filter-checkbox[value="comercial"]').is(':checked');
        const orderBy = $('#order-by').val() || 'registered_desc';
        
        console.log('🔄 Cargando clientes - Búsqueda:', searchTerm || '(sin filtro)', '- Página:', currentPage, '- Orden:', orderBy);
        
        const $customersList = $('#customers-list');
        const $pagination = $('#classification-pagination');
        const $resultsCount = $('#search-results-count');
        
        // Mostrar loading
        $customersList.html(`
            <tr>
                <td colspan="11" class="loading-row">
                    <i class="fas fa-spinner fa-spin"></i>
                    Cargando clientes...
                </td>
            </tr>
        `);
        
        // AJAX
        $.ajax({
            url: wcfmVendorClassification.ajax_url,
            type: 'POST',
            data: {
                action: 'wcfm_search_customers_classification',
                nonce: wcfmVendorClassification.nonce,
                search: searchTerm,
                page: currentPage,
                order_by: orderBy,
                filter_revisado: filterRevisado ? 'true' : 'false',
                filter_contrato: filterContrato ? 'true' : 'false',
                filter_interesa: filterInteresa ? 'true' : 'false',
                filter_en_espera: filterEnEspera ? 'true' : 'false',
                filter_no_interesa: filterNoInteresa ? 'true' : 'false',
                filter_comercial: filterComercial ? 'true' : 'false'
            },
            success: function(response) {
                console.log('✅ Clientes cargados:', response);
                
                if (response.success) {
                    const data = response.data;
                    totalCustomers = data.total;
                    
                    // Actualizar contador
                    if (searchTerm) {
                        $resultsCount.html(`Se encontraron <strong>${data.total}</strong> cliente(s) con "<strong>${searchTerm}</strong>"`);
                    } else {
                        $resultsCount.html(`Total: <strong>${data.total}</strong> clientes`);
                    }
                    
                    // Renderizar clientes
                    if (data.customers.length > 0) {
                        displayCustomers(data.customers);
                        displayPagination(data.pages, data.current_page, data.total, data.per_page);
                    } else {
                        $customersList.html(`
                            <tr class="no-results-row">
                                <td colspan="11">
                                    <i class="fas fa-search"></i>
                                    <div>No se encontraron clientes con los criterios de búsqueda.</div>
                                </td>
                            </tr>
                        `);
                        $pagination.hide();
                    }
                } else {
                    console.error('❌ Error en respuesta:', response);
                    $customersList.html(`
                        <tr class="no-results-row">
                            <td colspan="11">
                                <i class="fas fa-exclamation-triangle"></i>
                                <div>Error al cargar clientes. Por favor, inténtalo de nuevo.</div>
                            </td>
                        </tr>
                    `);
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Error en AJAX:', {xhr, status, error});
                $customersList.html(`
                    <tr class="no-results-row">
                        <td colspan="11">
                            <i class="fas fa-times-circle"></i>
                            <div>Error de conexión. Por favor, recarga la página.</div>
                        </td>
                    </tr>
                `);
            }
        });
    }
    
    /**
     * Mostrar clientes en la tabla
     */
    function displayCustomers(customers) {
        const $customersList = $('#customers-list');
        let html = '';
        
        customers.forEach(function(customer) {
            html += `
                <tr data-customer-id="${customer.id}">
                    <td class="customer-column">
                        <div class="customer-info">
                            <span class="customer-name">${escapeHtml(customer.full_name)}</span>
                            <span class="customer-login">@${escapeHtml(customer.user_login)}</span>
                        </div>
                    </td>
                    <td class="email-column">
                        <a href="mailto:${escapeHtml(customer.email)}" class="customer-email" title="${escapeHtml(customer.email)}">
                            ${escapeHtml(customer.email.length > 25 ? customer.email.substring(0, 25) + '...' : customer.email)}
                        </a>
                    </td>
                    <td class="phone-column">
                        ${escapeHtml(customer.phone || '-')}
                    </td>
                    <td class="revisado-column">
                        <div class="classification-checkbox">
                            <input 
                                type="checkbox" 
                                class="revisado-checkbox" 
                                ${customer.revisado ? 'checked' : ''}
                            >
                        </div>
                    </td>
                    <td class="contrato-column">
                        <div class="classification-checkbox">
                            <input 
                                type="checkbox" 
                                class="contrato-checkbox" 
                                ${customer.contrato ? 'checked' : ''}
                            >
                        </div>
                    </td>
                    <td class="interesa-column">
                        <div class="classification-checkbox">
                            <input 
                                type="checkbox" 
                                class="interesa-checkbox" 
                                ${customer.interesa ? 'checked' : ''}
                            >
                        </div>
                    </td>
                    <td class="en_espera-column">
                        <div class="classification-checkbox">
                            <input 
                                type="checkbox" 
                                class="en_espera-checkbox" 
                                ${customer.en_espera ? 'checked' : ''}
                            >
                        </div>
                    </td>
                    <td class="no_interesa-column">
                        <div class="classification-checkbox">
                            <input 
                                type="checkbox" 
                                class="no_interesa-checkbox" 
                                ${customer.no_interesa ? 'checked' : ''}
                            >
                        </div>
                    </td>
                    <td class="comercio-column">
                        <div class="classification-checkbox">
                            <input 
                                type="checkbox" 
                                class="comercio-checkbox" 
                                ${customer.comercio ? 'checked' : ''}
                            >
                        </div>
                    </td>
                    <td class="comercial-column">
                        <div class="classification-checkbox">
                            <input 
                                type="checkbox" 
                                class="comercial-checkbox" 
                                ${customer.comercial ? 'checked' : ''}
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
        
        $customersList.html(html);
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
        html += `Mostrando ${start} - ${end} de ${total} clientes`;
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

