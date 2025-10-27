/**
 * WCFM Mobile Menu Fix
 * Mejora la usabilidad del menú en móviles
 */
(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Detectar si estamos en móvil
        function isMobile() {
            return window.innerWidth <= 768;
        }
        
        if (!isMobile() || !$('#wcfm_menu').length) {
            return; // Solo ejecutar en móvil y si existe el menú
        }
        
        console.log('📱 WCFM Mobile Menu Fix cargado');
        
        // Añadir botón toggle al menú
        function addToggleButton() {
            if ($('.wcfm-mobile-menu-toggle-btn').length) {
                return; // Ya existe
            }
            
            const $toggleBtn = $('<button class="wcfm-mobile-menu-toggle-btn">📋 Menú ▼</button>');
            $('#wcfm_menu').prepend($toggleBtn);
            
            console.log('📱 Botón toggle añadido');
        }
        
        // Toggle del menú
        function toggleMenu() {
            const $body = $('body');
            const $btn = $('.wcfm-mobile-menu-toggle-btn');
            
            if ($body.hasClass('wcfm-mobile-menu-collapsed')) {
                // Expandir
                $body.removeClass('wcfm-mobile-menu-collapsed');
                $btn.html('📋 Menú ▲');
                console.log('📱 Menú expandido');
            } else {
                // Colapsar
                $body.addClass('wcfm-mobile-menu-collapsed');
                $btn.html('📋 Menú ▼');
                console.log('📱 Menú colapsado');
            }
        }
        
        // Añadir botón al cargar
        setTimeout(addToggleButton, 500);
        
        // Event listener para el botón toggle
        $(document).on('click', '.wcfm-mobile-menu-toggle-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleMenu();
        });
        
        // Al hacer clic en una opción del menú, colapsar automáticamente
        $(document).on('click', '#wcfm_menu .wcfm_menu_items a.wcfm_menu_item', function(e) {
            const menuText = $(this).find('.text').text().trim();
            console.log('📱 Click en:', menuText);
            
            // Colapsar menú después de navegar
            setTimeout(function() {
                $('body').addClass('wcfm-mobile-menu-collapsed');
                $('.wcfm-mobile-menu-toggle-btn').html('📋 Menú ▼');
                console.log('📱 Menú auto-colapsado');
            }, 500);
        });
        
        // Empezar colapsado para que el contenido sea visible
        setTimeout(function() {
            $('body').addClass('wcfm-mobile-menu-collapsed');
            console.log('📱 Menú inicialmente colapsado');
        }, 1000);
        
    });
    
})(jQuery);
