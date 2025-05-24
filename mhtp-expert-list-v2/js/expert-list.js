/**
 * MHTP Expert List JavaScript
 */

(function($) {
    'use strict';

    // Objeto principal
    const MHTPExpertList = {
        
        // Inicialización
        init: function() {
            this.setupFilterSelect();
            this.setupSearchFilter();
            this.setupHoverEffects();
        },
        
        // Configurar select de filtro por especialidad
        setupFilterSelect: function() {
            // Configurar el select de filtro basado en las etiquetas de los productos
            const $container = $('.mhtp-expert-list-container');
            
            if ($container.length) {
                // Obtener todas las tarjetas de expertos
                const $expertCards = $('.mhtp-expert-card');
                
                // Si hay expertos y existe el select de filtro
                if ($expertCards.length > 0 && $('.mhtp-expert-filter-select').length > 0) {
                    const $select = $('.mhtp-expert-filter-select');
                    
                    // Manejar cambios en el select
                    $select.on('change', function() {
                        const filter = $(this).val();
                        
                        // Filtrar tarjetas
                        if (filter === 'all') {
                            $expertCards.show();
                        } else {
                            $expertCards.hide();
                            $expertCards.each(function() {
                                const tags = $(this).data('tags');
                                if (tags && tags.split(',').includes(filter)) {
                                    $(this).show();
                                }
                            });
                        }
                    });
                }
            }
        },
        
        // Configurar filtro de búsqueda
        setupSearchFilter: function() {
            const $container = $('.mhtp-expert-list-container');
            
            if ($container.length) {
                // Crear campo de búsqueda si no existe
                if ($('.mhtp-expert-search').length === 0) {
                    $container.prepend('<div class="mhtp-expert-search"><input type="text" placeholder="Buscar experto..." class="mhtp-expert-search-input"></div>');
                }
                
                // Manejar eventos de búsqueda
                $('.mhtp-expert-search-input').on('keyup', function() {
                    const searchTerm = $(this).val().toLowerCase();
                    
                    // Filtrar tarjetas según término de búsqueda
                    $('.mhtp-expert-card').each(function() {
                        const title = $(this).find('.mhtp-expert-title').text().toLowerCase();
                        const description = $(this).find('.mhtp-expert-description').text().toLowerCase();
                        
                        if (title.includes(searchTerm) || description.includes(searchTerm)) {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                    });
                    
                    // Resetear filtros activos
                    $('.mhtp-expert-filter-select').val('all');
                });
            }
        },
        
        // Configurar efectos de hover
        setupHoverEffects: function() {
            // Los efectos de hover están principalmente en CSS, pero podemos añadir efectos adicionales aquí
            $('.mhtp-expert-card').hover(
                function() {
                    $(this).find('.mhtp-expert-title').css('color', '#3a5998'); // Cambiado a azul para combinar con el botón
                },
                function() {
                    $(this).find('.mhtp-expert-title').css('color', '#333');
                }
            );
        },
        
        // Verificar si el usuario tiene sesiones disponibles
        checkAvailableSessions: function() {
            // Esta función se podría implementar en el futuro para verificar si el usuario
            // tiene sesiones disponibles antes de mostrar el botón "Comenzar consulta"
            $.ajax({
                url: mhtp_expert_list_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'mhtp_check_available_sessions',
                    nonce: mhtp_expert_list_vars.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Actualizar UI basado en sesiones disponibles
                        if (response.data.has_sessions) {
                            $('.mhtp-expert-consult-btn').show();
                        } else {
                            $('.mhtp-expert-consult-btn').hide();
                        }
                    }
                }
            });
        }
    };
    
    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        MHTPExpertList.init();
    });
    
})(jQuery);
