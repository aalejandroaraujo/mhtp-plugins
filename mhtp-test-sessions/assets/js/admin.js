// Admin JavaScript for MHTP Test Sessions

jQuery(document).ready(function($) {
    // Variables
    var userListTable = $('#mhtp-ts-users-table-container');
    var addSessionsModal = $('#mhtp-ts-add-sessions-modal');
    var viewSessionsModal = $('#mhtp-ts-view-sessions-modal');
    
    // Filter users
    $('#mhtp-ts-users-filter-form').on('submit', function(e) {
        e.preventDefault();
        
        var search = $('#mhtp-ts-users-search').val();
        var role = $('#mhtp-ts-users-role').val();
        var hasSessions = $('#mhtp-ts-users-has-sessions').val();
        
        filterUsers(search, role, hasSessions, 1);
    });
    
    // Reset filters
    $('#mhtp-ts-users-reset').on('click', function() {
        $('#mhtp-ts-users-search').val('');
        $('#mhtp-ts-users-role').val('');
        $('#mhtp-ts-users-has-sessions').val('');
        
        filterUsers('', '', '', 1);
    });
    
    // Pagination click
    $(document).on('click', '.mhtp-ts-pagination a.button:not(.disabled)', function(e) {
        e.preventDefault();
        
        var page = getParameterByName('paged', $(this).attr('href')) || 1;
        var search = $('#mhtp-ts-users-search').val();
        var role = $('#mhtp-ts-users-role').val();
        var hasSessions = $('#mhtp-ts-users-has-sessions').val();
        
        filterUsers(search, role, hasSessions, page);
    });
    
    // Open add sessions modal
    $(document).on('click', '.mhtp-ts-add-sessions-btn', function() {
        var userId = $(this).data('user-id');
        var userName = $(this).data('user-name');
        var userEmail = $(this).closest('tr').find('.column-email').text().trim();
        
        $('#mhtp-ts-modal-user-id').val(userId);
        $('#mhtp-ts-modal-user-info').html('<div class="mhtp-ts-user-info"><h3>' + userName + '</h3><p>' + userEmail + '</p></div>');
        
        addSessionsModal.show();
    });
    
    // Open view sessions modal
    $(document).on('click', '.mhtp-ts-view-sessions-btn', function() {
        var userId = $(this).data('user-id');
        var userName = $(this).data('user-name');
        var userEmail = $(this).closest('tr').find('.column-email').text().trim();
        
        $('#mhtp-ts-modal-sessions-user-info').html('<div class="mhtp-ts-user-info"><h3>' + userName + '</h3><p>' + userEmail + '</p></div>');
        
        $('#mhtp-ts-modal-sessions-container').html('<p class="mhtp-ts-loading">Cargando sesiones...</p>');
        
        viewSessionsModal.show();
        
        // Get user sessions
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mhtp_ts_get_user_sessions',
                nonce: $('#mhtp-ts-admin-nonce').val(),
                user_id: userId
            },
            success: function(response) {
                if (response.success) {
                    var sessions = response.data.sessions;
                    var html = '';
                    
                    if (sessions && sessions.length > 0) {
                        html += '<table class="wp-list-table widefat fixed striped">';
                        html += '<thead><tr>';
                        html += '<th>ID</th>';
                        html += '<th>Categoría</th>';
                        html += '<th>Cantidad</th>';
                        html += '<th>Fecha de Creación</th>';
                        html += '<th>Fecha de Caducidad</th>';
                        html += '<th>Estado</th>';
                        html += '<th>Acciones</th>';
                        html += '</tr></thead><tbody>';
                        
                        for (var i = 0; i < sessions.length; i++) {
                            var session = sessions[i];
                            html += '<tr>';
                            html += '<td>' + session.id + '</td>';
                            html += '<td><span class="mhtp-ts-category mhtp-ts-category-' + session.category + '">' + session.category + '</span></td>';
                            html += '<td>' + session.quantity + '</td>';
                            html += '<td>' + session.created_at + '</td>';
                            html += '<td>' + (session.expiry_date ? session.expiry_date : 'Sin caducidad') + '</td>';
                            html += '<td><span class="mhtp-ts-status mhtp-ts-status-' + session.status + '">' + session.status + '</span></td>';
                            html += '<td><button class="button button-small mhtp-ts-delete-session" data-session-id="' + session.id + '">Eliminar</button></td>';
                            html += '</tr>';
                        }
                        
                        html += '</tbody></table>';
                    } else {
                        html = '<p class="mhtp-ts-no-sessions">Este usuario no tiene sesiones de prueba activas.</p>';
                    }
                    
                    $('#mhtp-ts-modal-sessions-container').html(html);
                } else {
                    $('#mhtp-ts-modal-sessions-container').html('<p class="mhtp-ts-error">' + response.data.message + '</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error AJAX:', xhr.responseText);
                $('#mhtp-ts-modal-sessions-container').html('<p class="mhtp-ts-error">Ha ocurrido un error al cargar las sesiones. Por favor, inténtalo de nuevo.</p>');
            }
        });
    });
    
    // Close modals
    $('.mhtp-ts-modal-close, .mhtp-ts-modal-cancel').on('click', function() {
        addSessionsModal.hide();
        viewSessionsModal.hide();
    });
    
    // Close modal when clicking outside
    $(window).on('click', function(e) {
        if ($(e.target).hasClass('mhtp-ts-modal')) {
            addSessionsModal.hide();
            viewSessionsModal.hide();
        }
    });
    
    // Add sessions form submit
    $('#mhtp-ts-modal-add-sessions-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var submitBtn = form.find('button[type="submit"]');
        var errorContainer = form.find('.mhtp-ts-form-error');
        
        // Remove any existing error messages
        errorContainer.empty().hide();
        
        // Disable submit button
        submitBtn.prop('disabled', true).text('Procesando...');
        
        // Get form data
        var userId = $('#mhtp-ts-modal-user-id').val();
        var quantity = $('#mhtp-ts-modal-quantity').val();
        var category = $('#mhtp-ts-modal-category').val();
        var expiryDays = $('#mhtp-ts-modal-expiry').val();
        var notes = $('#mhtp-ts-modal-notes').val();
        
        // Validate form data
        if (!userId || userId <= 0) {
            showFormError(errorContainer, 'Usuario inválido');
            submitBtn.prop('disabled', false).text('Añadir Sesiones');
            return;
        }
        
        if (!quantity || quantity <= 0) {
            showFormError(errorContainer, 'La cantidad debe ser mayor que cero');
            submitBtn.prop('disabled', false).text('Añadir Sesiones');
            return;
        }
        
        // Send AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mhtp_ts_add_sessions',
                nonce: $('#mhtp-ts-admin-nonce').val(),
                user_id: userId,
                quantity: quantity,
                category: category,
                expiry_days: expiryDays,
                notes: notes
            },
            success: function(response) {
                // Enable submit button
                submitBtn.prop('disabled', false).text('Añadir Sesiones');
                
                if (response.success) {
                    // Show success message
                    alert(response.data.message);
                    
                    // Close modal
                    addSessionsModal.hide();
                    
                    // Reset form
                    form[0].reset();
                    
                    // Refresh user list
                    var search = $('#mhtp-ts-users-search').val();
                    var role = $('#mhtp-ts-users-role').val();
                    var hasSessions = $('#mhtp-ts-users-has-sessions').val();
                    var page = getParameterByName('paged', window.location.href) || 1;
                    
                    filterUsers(search, role, hasSessions, page);
                } else {
                    // Show error message
                    showFormError(errorContainer, response.data.message);
                }
            },
            error: function(xhr, status, error) {
                // Enable submit button
                submitBtn.prop('disabled', false).text('Añadir Sesiones');
                
                // Log error to console
                console.error('Error AJAX:', xhr.responseText);
                
                // Show error message
                showFormError(errorContainer, 'Ha ocurrido un error al procesar la solicitud. Por favor, inténtalo de nuevo.');
            }
        });
    });
    
    // Delete session
    $(document).on('click', '.mhtp-ts-delete-session', function() {
        if (confirm('¿Estás seguro de que deseas eliminar esta sesión?')) {
            var button = $(this);
            var sessionId = button.data('session-id');
            var row = button.closest('tr');
            
            // Disable button
            button.prop('disabled', true).text('Eliminando...');
            
            // Send AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'mhtp_ts_delete_session',
                    nonce: $('#mhtp-ts-admin-nonce').val(),
                    session_id: sessionId
                },
                success: function(response) {
                    if (response.success) {
                        // Remove row
                        row.fadeOut(300, function() {
                            row.remove();
                            
                            // Check if no sessions left
                            if ($('#mhtp-ts-modal-sessions-container table tbody tr').length === 0) {
                                $('#mhtp-ts-modal-sessions-container').html('<p class="mhtp-ts-no-sessions">Este usuario no tiene sesiones de prueba activas.</p>');
                            }
                        });
                        
                        // Refresh user list
                        var search = $('#mhtp-ts-users-search').val();
                        var role = $('#mhtp-ts-users-role').val();
                        var hasSessions = $('#mhtp-ts-users-has-sessions').val();
                        var page = getParameterByName('paged', window.location.href) || 1;
                        
                        filterUsers(search, role, hasSessions, page);
                    } else {
                        // Show error message
                        alert(response.data.message);
                        button.prop('disabled', false).text('Eliminar');
                    }
                },
                error: function(xhr, status, error) {
                    // Enable button
                    button.prop('disabled', false).text('Eliminar');
                    
                    // Log error to console
                    console.error('Error AJAX:', xhr.responseText);
                    
                    // Show error message
                    alert('Ha ocurrido un error al procesar la solicitud. Por favor, inténtalo de nuevo.');
                }
            });
        }
    });
    
    // Helper function to filter users
    function filterUsers(search, role, hasSessions, page) {
        userListTable.html('<p class="mhtp-ts-loading">Cargando usuarios...</p>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mhtp_ts_get_filtered_users',
                nonce: $('#mhtp-ts-admin-nonce').val(),
                search: search,
                role: role,
                has_sessions: hasSessions,
                page: page
            },
            success: function(response) {
                if (response.success) {
                    // Build table HTML
                    var html = '<table class="wp-list-table widefat fixed striped mhtp-ts-users-table">';
                    html += '<thead><tr>';
                    html += '<th class="column-username">Usuario</th>';
                    html += '<th class="column-email">Email</th>';
                    html += '<th class="column-role">Rol</th>';
                    html += '<th class="column-registered">Registro</th>';
                    html += '<th class="column-sessions">Sesiones</th>';
                    html += '<th class="column-actions">Acciones</th>';
                    html += '</tr></thead><tbody>';
                    
                    if (response.data.users.length > 0) {
                        $.each(response.data.users, function(index, user) {
                            html += '<tr>';
                            html += '<td class="column-username"><strong>' + user.display_name + '</strong></td>';
                            html += '<td class="column-email">' + user.email + '</td>';
                            html += '<td class="column-role">' + user.roles + '</td>';
                            html += '<td class="column-registered">' + user.registered + '</td>';
                            html += '<td class="column-sessions">';
                            html += '<span class="mhtp-ts-sessions-count ' + (user.sessions_count > 0 ? 'has-sessions' : 'no-sessions') + '">';
                            html += user.sessions_count;
                            html += '</span></td>';
                            html += '<td class="column-actions">';
                            html += '<button type="button" class="button mhtp-ts-add-sessions-btn" data-user-id="' + user.id + '" data-user-name="' + user.display_name + '">';
                            html += 'Añadir Sesiones</button> ';
                            
                            if (user.sessions_count > 0) {
                                html += '<button type="button" class="button mhtp-ts-view-sessions-btn" data-user-id="' + user.id + '" data-user-name="' + user.display_name + '">';
                                html += 'Ver Sesiones</button>';
                            }
                            
                            html += '</td></tr>';
                        });
                    } else {
                        html += '<tr><td colspan="6">No se encontraron usuarios</td></tr>';
                    }
                    
                    html += '</tbody></table>';
                    
                    // Add pagination
                    if (response.data.pagination) {
                        html += '<div class="mhtp-ts-pagination">' + response.data.pagination + '</div>';
                    }
                    
                    userListTable.html(html);
                    
                    // Update URL with filter parameters
                    var url = window.location.href.split('?')[0] + '?page=mhtp-ts-user-list';
                    
                    if (search) {
                        url += '&search=' + encodeURIComponent(search);
                    }
                    
                    if (role) {
                        url += '&role=' + encodeURIComponent(role);
                    }
                    
                    if (hasSessions) {
                        url += '&has_sessions=' + encodeURIComponent(hasSessions);
                    }
                    
                    if (page > 1) {
                        url += '&paged=' + page;
                    }
                    
                    window.history.replaceState({}, '', url);
                } else {
                    userListTable.html('<p class="mhtp-ts-error">' + response.data.message + '</p>');
                }
            },
            error: function(xhr, status, error) {
                // Log error to console
                console.error('Error AJAX:', xhr.responseText);
                
                // Show error message
                userListTable.html('<p class="mhtp-ts-error">Ha ocurrido un error al cargar los usuarios. Por favor, inténtalo de nuevo.</p>');
            }
        });
    }
    
    // Helper function to show form error
    function showFormError(container, message) {
        container.html('<div class="notice notice-error"><p>' + message + '</p></div>').show();
    }
    
    // Helper function to get URL parameter
    function getParameterByName(name, url) {
        if (!url) url = window.location.href;
        name = name.replace(/[\[\]]/g, '\\$&');
        var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
            results = regex.exec(url);
        if (!results) return null;
        if (!results[2]) return '';
        return decodeURIComponent(results[2].replace(/\+/g, ' '));
    }
    
    // Add hidden nonce field to body for AJAX requests
    $('body').append('<input type="hidden" id="mhtp-ts-admin-nonce" value="' + mhtp_ts_admin.nonce + '">');
    
    // Initialize page with URL parameters
    var urlSearch = getParameterByName('search');
    var urlRole = getParameterByName('role');
    var urlHasSessions = getParameterByName('has_sessions');
    var urlPage = getParameterByName('paged') || 1;
    
    if (urlSearch || urlRole || urlHasSessions) {
        $('#mhtp-ts-users-search').val(urlSearch);
        $('#mhtp-ts-users-role').val(urlRole);
        $('#mhtp-ts-users-has-sessions').val(urlHasSessions);
        
        filterUsers(urlSearch, urlRole, urlHasSessions, urlPage);
    }
});
