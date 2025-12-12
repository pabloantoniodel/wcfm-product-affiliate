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
         * Solo escuchar cambios en checkboxes dentro del contenedor de filtros
         */
        $(document).on('change', '.classification-filters .filter-checkbox', function() {
            currentPage = 1;
            // Buscar inmediatamente sin debounce
            clearTimeout(searchTimeout);
            loadCustomers();
        });
        
        /**
         * Cambio en selector de orden - Buscar inmediatamente
         * IMPORTANTE: El orden siempre se aplica con AND respecto a los filtros
         */
        $('#order-by').on('change', function() {
            const newOrder = $(this).val();
            console.log('🔄 Cambio de orden detectado:', newOrder);
            currentPage = 1;
            // Buscar inmediatamente sin debounce
            clearTimeout(searchTimeout);
            // Aumentar delay para asegurar que el DOM esté completamente actualizado
            // y que los filtros se lean correctamente
            setTimeout(function() {
                console.log('🔄 Ejecutando loadCustomers después de cambio de orden...');
                loadCustomers();
            }, 50);
        });
        
        /**
         * Cambio en lógica de filtros (AND/OR) - Buscar inmediatamente
         */
        $('input[name="filter-logic"]').on('change', function() {
            currentPage = 1;
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
     * Mostrar overlay de carga
     */
    function showLoadingOverlay() {
        $('#classification-loading-overlay').fadeIn(200);
    }
    
    /**
     * Ocultar overlay de carga
     */
    function hideLoadingOverlay() {
        $('#classification-loading-overlay').fadeOut(200);
    }
    
    /**
     * Cargar clientes
     */
    function loadCustomers() {
        const searchTerm = $('#customer-search').val().trim();
        // Leer filtros solo de los checkboxes de filtro (no de la tabla)
        // Usar selector más específico para evitar conflictos con checkboxes de la tabla
        // Asegurarse de leer desde el contenedor de filtros, no de la tabla
        const $filterContainer = $('.classification-filters');
        if ($filterContainer.length === 0) {
            console.error('❌ No se encontró el contenedor de filtros');
            return;
        }
        
        // Leer el estado de cada checkbox directamente del DOM
        // Usar prop('checked') en lugar de is(':checked') para mayor confiabilidad
        const $checkboxRevisado = $filterContainer.find('input.filter-checkbox[value="revisado"]');
        const $checkboxContrato = $filterContainer.find('input.filter-checkbox[value="contrato"]');
        const $checkboxInteresa = $filterContainer.find('input.filter-checkbox[value="interesa"]');
        const $checkboxEnEspera = $filterContainer.find('input.filter-checkbox[value="en_espera"]');
        const $checkboxNoInteresa = $filterContainer.find('input.filter-checkbox[value="no_interesa"]');
        const $checkboxComercial = $filterContainer.find('input.filter-checkbox[value="comercial"]');
        const $checkboxComercio = $filterContainer.find('input.filter-checkbox[value="comercio"]');
        
        // Leer el estado checked usando prop() que es más confiable que is(':checked')
        const filterRevisado = $checkboxRevisado.length > 0 ? ($checkboxRevisado.prop('checked') === true) : false;
        const filterContrato = $checkboxContrato.length > 0 ? ($checkboxContrato.prop('checked') === true) : false;
        const filterInteresa = $checkboxInteresa.length > 0 ? ($checkboxInteresa.prop('checked') === true) : false;
        const filterEnEspera = $checkboxEnEspera.length > 0 ? ($checkboxEnEspera.prop('checked') === true) : false;
        const filterNoInteresa = $checkboxNoInteresa.length > 0 ? ($checkboxNoInteresa.prop('checked') === true) : false;
        const filterComercial = $checkboxComercial.length > 0 ? ($checkboxComercial.prop('checked') === true) : false;
        const filterComercio = $checkboxComercio.length > 0 ? ($checkboxComercio.prop('checked') === true) : false;
        const filterLogic = $('input[name="filter-logic"]:checked').val() || 'AND';
        const orderBy = $('#order-by').val() || 'registered_desc';
        
        // Debug: Verificar el estado del checkbox de revisado específicamente
        if ($checkboxRevisado.length > 0) {
            console.log('🔍 DEBUG Checkbox Revisado:', {
                encontrado: true,
                checked: $checkboxRevisado.is(':checked'),
                prop_checked: $checkboxRevisado.prop('checked'),
                attr_checked: $checkboxRevisado.attr('checked'),
                valor: $checkboxRevisado.val()
            });
        } else {
            console.warn('⚠️ Checkbox Revisado NO encontrado en el contenedor de filtros');
        }
        
        console.log('🔄 Cargando clientes - Búsqueda:', searchTerm || '(sin filtro)', '- Página:', currentPage, '- Orden:', orderBy, '- Lógica:', filterLogic);
        console.log('🔍 Filtros activos (desde contenedor de filtros):', {
            revisado: filterRevisado,
            contrato: filterContrato,
            interesa: filterInteresa,
            en_espera: filterEnEspera,
            no_interesa: filterNoInteresa,
            comercial: filterComercial,
            comercio: filterComercio
        });
        
        const $customersList = $('#customers-list');
        const $pagination = $('#classification-pagination');
        const $resultsCount = $('#search-results-count');
        
        // Mostrar overlay de carga
        showLoadingOverlay();
        
        // Mostrar loading
        $customersList.html(`
            <tr>
                <td colspan="10" class="loading-row">
                    <i class="fas fa-spinner fa-spin"></i>
                    Cargando clientes...
                </td>
            </tr>
        `);
        
        // Preparar datos para AJAX
        const ajaxData = {
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
            filter_comercial: filterComercial ? 'true' : 'false',
            filter_comercio: filterComercio ? 'true' : 'false',
            filter_logic: filterLogic
        };
        
        console.log('📤 Datos enviados al servidor:', ajaxData);
        
        // AJAX
        $.ajax({
            url: wcfmVendorClassification.ajax_url,
            type: 'POST',
            data: ajaxData,
            success: function(response) {
                console.log('✅ Clientes cargados:', response);
                
                // Ocultar overlay de carga
                hideLoadingOverlay();
                
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
                                <td colspan="10">
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
                            <td colspan="10">
                                <i class="fas fa-exclamation-triangle"></i>
                                <div>Error al cargar clientes. Por favor, inténtalo de nuevo.</div>
                            </td>
                        </tr>
                    `);
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Error en AJAX:', {xhr, status, error});
                
                // Ocultar overlay de carga
                hideLoadingOverlay();
                
                $customersList.html(`
                    <tr class="no-results-row">
                        <td colspan="10">
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
                    <td class="phone-column">
                        ${escapeHtml(customer.phone || '-')}
                    </td>
                    <td class="code-column">
                        <div class="cv-code-cell" data-customer-id="${customer.id}">
                            <span class="cv-code-display">${escapeHtml(customer.code || '—')}</span>
                            <input type="text" class="cv-code-input" value="${escapeHtml(customer.code || '')}" style="display:none;" />
                            <button type="button" class="cv-code-edit-btn" title="Editar código">✎</button>
                        </div>
                    </td>
                    <td class="cv-classification-column" style="display: none;">
                        <div class="cv-classification-cell" data-customer-id="${customer.id}">
                            <span class="cv-classification-display">${escapeHtml(customer.cv_classification || '—')}</span>
                            <input type="text" class="cv-classification-input" value="${escapeHtml(customer.cv_classification || '')}" style="display:none;" />
                            <button type="button" class="cv-classification-edit-btn" title="Editar clasificación CV">✎</button>
                        </div>
                    </td>
                    <td class="revisado-column">
                        <div class="classification-checkbox" title="${customer.is_active ? 'Cliente activo' : 'Cliente inactivo'}">
                            <input 
                                type="checkbox" 
                                class="revisado-checkbox" 
                                ${customer.revisado ? 'checked' : ''}
                            >
                            ${customer.is_active ? '<span style="color: #00a32a; font-size: 10px; margin-left: 3px;" title="Activo">●</span>' : '<span style="color: #dc3232; font-size: 10px; margin-left: 3px;" title="Inactivo">●</span>'}
                        </div>
                    </td>
                    <td class="contrato-column">
                        <div class="classification-checkbox" title="${customer.is_active ? 'Cliente activo' : 'Cliente inactivo'}">
                            <input 
                                type="checkbox" 
                                class="contrato-checkbox" 
                                ${customer.contrato ? 'checked' : ''}
                            >
                            ${customer.is_active ? '<span style="color: #00a32a; font-size: 10px; margin-left: 3px;" title="Activo">●</span>' : '<span style="color: #dc3232; font-size: 10px; margin-left: 3px;" title="Inactivo">●</span>'}
                        </div>
                    </td>
                    <td class="interesa-column">
                        <div class="classification-checkbox" title="${customer.is_active ? 'Cliente activo' : 'Cliente inactivo'}">
                            <input 
                                type="checkbox" 
                                class="interesa-checkbox" 
                                ${customer.interesa ? 'checked' : ''}
                            >
                            ${customer.is_active ? '<span style="color: #00a32a; font-size: 10px; margin-left: 3px;" title="Activo">●</span>' : '<span style="color: #dc3232; font-size: 10px; margin-left: 3px;" title="Inactivo">●</span>'}
                        </div>
                    </td>
                    <td class="en_espera-column">
                        <div class="classification-checkbox" title="${customer.is_active ? 'Cliente activo' : 'Cliente inactivo'}">
                            <input 
                                type="checkbox" 
                                class="en_espera-checkbox" 
                                ${customer.en_espera ? 'checked' : ''}
                            >
                            ${customer.is_active ? '<span style="color: #00a32a; font-size: 10px; margin-left: 3px;" title="Activo">●</span>' : '<span style="color: #dc3232; font-size: 10px; margin-left: 3px;" title="Inactivo">●</span>'}
                        </div>
                    </td>
                    <td class="no_interesa-column">
                        <div class="classification-checkbox" title="${customer.is_active ? 'Cliente activo' : 'Cliente inactivo'}">
                            <input 
                                type="checkbox" 
                                class="no_interesa-checkbox" 
                                ${customer.no_interesa ? 'checked' : ''}
                            >
                            ${customer.is_active ? '<span style="color: #00a32a; font-size: 10px; margin-left: 3px;" title="Activo">●</span>' : '<span style="color: #dc3232; font-size: 10px; margin-left: 3px;" title="Inactivo">●</span>'}
                        </div>
                    </td>
                    <td class="comercio-column">
                        <div class="classification-checkbox" title="${customer.is_active ? 'Cliente activo' : 'Cliente inactivo'}">
                            <input 
                                type="checkbox" 
                                class="comercio-checkbox" 
                                ${customer.comercio ? 'checked' : ''}
                            >
                            ${customer.is_active ? '<span style="color: #00a32a; font-size: 10px; margin-left: 3px;" title="Activo">●</span>' : '<span style="color: #dc3232; font-size: 10px; margin-left: 3px;" title="Inactivo">●</span>'}
                        </div>
                    </td>
                    <td class="comercial-column">
                        <div class="classification-checkbox" title="${customer.is_active ? 'Cliente activo' : 'Cliente inactivo'}">
                            <input 
                                type="checkbox" 
                                class="comercial-checkbox" 
                                ${customer.comercial ? 'checked' : ''}
                            >
                            ${customer.is_active ? '<span style="color: #00a32a; font-size: 10px; margin-left: 3px;" title="Activo">●</span>' : '<span style="color: #dc3232; font-size: 10px; margin-left: 3px;" title="Inactivo">●</span>'}
                        </div>
                    </td>
                    <td class="actions-column">
                        <button type="button" class="save-classification-btn" disabled>
                            <i class="fas fa-save"></i>
                            <span>Guardar</span>
                        </button>
                        <div class="actions-buttons-vertical">
                            <a href="${customer.store_manager_url}" target="_blank" class="button button-small store-manager-btn" title="Ir a la Tienda">
                                <i class="fas fa-store"></i>
                                Tienda
                            </a>
                            <button type="button" class="button button-small crm-link-btn ${(customer.crm_link && customer.crm_link.trim() !== '') ? 'crm-link-btn-has-link' : 'crm-link-btn-no-link'}" data-customer-id="${customer.id}" data-crm-link="${escapeHtml(customer.crm_link || '')}" title="Link a CRM">
                                <i class="fas fa-external-link-alt"></i>
                                CRM
                            </button>
                        </div>
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
    
    /**
     * Editar código ciudad virtual directamente en el listado
     */
    $(document).on('click', '.cv-code-edit-btn', function(event) {
        event.preventDefault();
        event.stopPropagation();
        
        const $cell = $(this).closest('.cv-code-cell');
        const $display = $cell.find('.cv-code-display');
        const $input = $cell.find('.cv-code-input');
        const $btn = $(this);
        
        if ($input.is(':visible')) {
            // Guardar
            const customerId = parseInt($cell.data('customer-id'), 10);
            const newCode = $input.val().trim();
            
            if (!customerId) {
                return;
            }
            
            $.post(
                wcfmVendorClassification.ajax_url,
                {
                    action: 'wcfm_update_customer_code',
                    customer_id: customerId,
                    code: newCode,
                    nonce: wcfmVendorClassification.nonce
                }
            ).done(function(response) {
                if (!response || !response.success) {
                    alert('Error al guardar el código.');
                    return;
                }
                
                $display.text(response.data.code || '—');
                $input.val(response.data.code || '');
                $input.hide();
                $display.show();
                $btn.text('✎');
            }).fail(function() {
                alert('Error al guardar el código.');
            });
        } else {
            // Editar
            $display.hide();
            $input.show().focus().select();
            $btn.text('✓');
            
            // Activar el botón de guardar de la clasificación
            const $row = $cell.closest('tr');
            const $saveBtn = $row.find('.save-classification-btn');
            if ($saveBtn.length) {
                $saveBtn.prop('disabled', false);
            }
        }
    });
    
    // Detectar cambios en el input del código CV para activar botón guardar
    $(document).on('input', '.cv-code-input', function() {
        const $cell = $(this).closest('.cv-code-cell');
        const $row = $cell.closest('tr');
        const $saveBtn = $row.find('.save-classification-btn');
        if ($saveBtn.length) {
            $saveBtn.prop('disabled', false);
        }
    });
    
    // Guardar al presionar Enter en el input
    $(document).on('keydown', '.cv-code-input', function(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            $(this).closest('.cv-code-cell').find('.cv-code-edit-btn').click();
        } else if (event.key === 'Escape') {
            event.preventDefault();
            const $cell = $(this).closest('.cv-code-cell');
            const $display = $cell.find('.cv-code-display');
            const $input = $cell.find('.cv-code-input');
            const $btn = $cell.find('.cv-code-edit-btn');
            $input.hide();
            $display.show();
            $btn.text('✎');
            // Restaurar valor original
            $input.val($display.text());
        }
    });
    
    /**
     * Editar clasificación CV directamente en el listado
     */
    $(document).on('click', '.cv-classification-edit-btn', function(event) {
        event.preventDefault();
        event.stopPropagation();
        
        const $cell = $(this).closest('.cv-classification-cell');
        const $display = $cell.find('.cv-classification-display');
        const $input = $cell.find('.cv-classification-input');
        const $btn = $(this);
        
        if ($input.is(':visible')) {
            // Guardar
            const customerId = parseInt($cell.data('customer-id'), 10);
            const newClassification = $input.val().trim();
            
            if (!customerId) {
                return;
            }
            
            $.post(
                wcfmVendorClassification.ajax_url,
                {
                    action: 'wcfm_update_customer_cv_classification',
                    customer_id: customerId,
                    cv_classification: newClassification,
                    nonce: wcfmVendorClassification.nonce
                }
            ).done(function(response) {
                if (!response || !response.success) {
                    alert('Error al guardar la clasificación CV.');
                    return;
                }
                
                $display.text(response.data.cv_classification || '—');
                $input.val(response.data.cv_classification || '');
                $input.hide();
                $display.show();
                $btn.text('✎');
            }).fail(function() {
                alert('Error al guardar la clasificación CV.');
            });
        } else {
            // Editar
            $display.hide();
            $input.show().focus().select();
            $btn.text('✓');
            
            // Activar el botón de guardar de la clasificación
            const $row = $cell.closest('tr');
            const $saveBtn = $row.find('.save-classification-btn');
            if ($saveBtn.length) {
                $saveBtn.prop('disabled', false);
            }
        }
    });
    
    // Detectar cambios en el input de clasificación CV para activar botón guardar
    $(document).on('input', '.cv-classification-input', function() {
        const $cell = $(this).closest('.cv-classification-cell');
        const $row = $cell.closest('tr');
        const $saveBtn = $row.find('.save-classification-btn');
        if ($saveBtn.length) {
            $saveBtn.prop('disabled', false);
        }
    });
    
    // Guardar al presionar Enter en el input de clasificación CV
    $(document).on('keydown', '.cv-classification-input', function(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            $(this).closest('.cv-classification-cell').find('.cv-classification-edit-btn').click();
        } else if (event.key === 'Escape') {
            event.preventDefault();
            const $cell = $(this).closest('.cv-classification-cell');
            const $display = $cell.find('.cv-classification-display');
            const $input = $cell.find('.cv-classification-input');
            const $btn = $cell.find('.cv-classification-edit-btn');
            $input.hide();
            $display.show();
            $btn.text('✎');
            // Restaurar valor original
            $input.val($display.text());
        }
    });
    
    // Cancelar edición al hacer clic fuera
    $(document).on('click', function(event) {
        if (!$(event.target).closest('.cv-code-cell').length) {
            $('.cv-code-input:visible').each(function() {
                const $cell = $(this).closest('.cv-code-cell');
                const $display = $cell.find('.cv-code-display');
                const $btn = $cell.find('.cv-code-edit-btn');
                $(this).hide();
                $display.show();
                $btn.text('✎');
            });
        }
    });
    
    /**
     * Botón Link a CRM - Mostrar modal y gestionar link
     */
    $(document).on('click', '.crm-link-btn', function(event) {
        event.preventDefault();
        event.stopPropagation();
        
        const $btn = $(this);
        const customerId = parseInt($btn.data('customer-id'), 10);
        let currentCrmLink = $btn.data('crm-link') || '';
        
        // Si no hay link, obtenerlo del servidor
        if (!currentCrmLink) {
            $.post(
                wcfmVendorClassification.ajax_url,
                {
                    action: 'wcfm_get_customer_crm_link',
                    customer_id: customerId,
                    nonce: wcfmVendorClassification.nonce
                }
            ).done(function(response) {
                if (response && response.success) {
                    currentCrmLink = response.data.crm_link || '';
                    showCrmLinkModal(customerId, currentCrmLink);
                } else {
                    showCrmLinkModal(customerId, '');
                }
            }).fail(function() {
                showCrmLinkModal(customerId, '');
            });
        } else {
            showCrmLinkModal(customerId, currentCrmLink);
        }
    });
    
    /**
     * Mostrar modal para editar link CRM
     */
    function showCrmLinkModal(customerId, crmLink) {
        // Crear modal si no existe
        if ($('#crm-link-modal').length === 0) {
            $('body').append(`
                <div id="crm-link-modal" class="crm-link-modal" style="display: none;">
                    <div class="crm-link-modal-overlay"></div>
                    <div class="crm-link-modal-content">
                        <div class="crm-link-modal-header">
                            <h3><i class="fas fa-external-link-alt"></i> Link a CRM</h3>
                            <button type="button" class="crm-link-modal-close" title="Cerrar">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="crm-link-modal-body">
                            <label for="crm-link-input">
                                <strong>URL del CRM:</strong>
                            </label>
                            <input 
                                type="url" 
                                id="crm-link-input" 
                                class="crm-link-input" 
                                placeholder="https://ejemplo.com/crm/cliente-123"
                                value=""
                            >
                            <p class="crm-link-help">
                                <i class="fas fa-info-circle"></i>
                                Ingresa la URL completa del CRM para este cliente.
                            </p>
                        </div>
                        <div class="crm-link-modal-footer">
                            <button type="button" class="crm-link-cancel-btn button">Cancelar</button>
                            <button type="button" class="crm-link-open-btn button" style="display: none;">Abrir</button>
                            <button type="button" class="crm-link-save-btn button button-primary">Guardar y Abrir</button>
                        </div>
                    </div>
                </div>
            `);
            
            // Eventos del modal
            $(document).on('click', '.crm-link-modal-close, .crm-link-modal-overlay, .crm-link-cancel-btn', function() {
                $('#crm-link-modal').fadeOut(200);
            });
            
            $(document).on('click', '.crm-link-open-btn', function() {
                const currentLink = $('#crm-link-input').val().trim();
                if (currentLink) {
                    window.open(currentLink, '_blank');
                }
            });
            
            $(document).on('click', '.crm-link-save-btn', function() {
                const newLink = $('#crm-link-input').val().trim();
                const modalCustomerId = parseInt($('#crm-link-modal').data('customer-id'), 10);
                
                if (!newLink) {
                    alert('Por favor, ingresa una URL válida.');
                    return;
                }
                
                // Validar URL básica
                try {
                    new URL(newLink);
                } catch (e) {
                    alert('Por favor, ingresa una URL válida (debe comenzar con http:// o https://).');
                    return;
                }
                
                // Guardar link
                $.post(
                    wcfmVendorClassification.ajax_url,
                    {
                        action: 'wcfm_update_customer_crm_link',
                        customer_id: modalCustomerId,
                        crm_link: newLink,
                        nonce: wcfmVendorClassification.nonce
                    }
                ).done(function(response) {
                    if (response && response.success) {
                        // Actualizar botón
                        const $btn = $(`.crm-link-btn[data-customer-id="${modalCustomerId}"]`);
                        $btn.data('crm-link', newLink);
                        
                        // Actualizar clase del botón según si tiene link o no
                        if (newLink && newLink.trim() !== '') {
                            $btn.removeClass('crm-link-btn-no-link').addClass('crm-link-btn-has-link');
                        } else {
                            $btn.removeClass('crm-link-btn-has-link').addClass('crm-link-btn-no-link');
                        }
                        
                        // Cerrar modal
                        $('#crm-link-modal').fadeOut(200);
                        
                        // Abrir link en nueva pestaña
                        if (newLink) {
                            window.open(newLink, '_blank');
                        }
                    } else {
                        alert('Error al guardar el link CRM.');
                    }
                }).fail(function() {
                    alert('Error al guardar el link CRM.');
                });
            });
            
            // Cerrar con Escape
            $(document).on('keydown', function(event) {
                if (event.key === 'Escape' && $('#crm-link-modal').is(':visible')) {
                    $('#crm-link-modal').fadeOut(200);
                }
            });
        }
        
        // Mostrar modal y establecer datos
        $('#crm-link-modal').data('customer-id', customerId);
        $('#crm-link-input').val(crmLink);
        
        // Mostrar/ocultar botón "Abrir" según si hay link
        if (crmLink && crmLink.trim() !== '') {
            $('.crm-link-open-btn').show();
        } else {
            $('.crm-link-open-btn').hide();
        }
        
        $('#crm-link-modal').fadeIn(200);
        $('#crm-link-input').focus().select();
    }
    
})(jQuery);

