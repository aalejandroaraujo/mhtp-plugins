<?php
/**
 * Template para la página principal
 *
 * @package MHTP_Test_Sessions
 */

// Si este archivo es llamado directamente, abortar
if (!defined('ABSPATH')) {
    exit;
}

// Obtener usuario seleccionado si se proporciona en la URL
$selected_user_id = isset($_GET['user_id']) ? absint($_GET['user_id']) : 0;
$selected_user = $selected_user_id > 0 ? get_userdata($selected_user_id) : null;

// Obtener todos los usuarios
$users_query = new WP_User_Query(array(
    'orderby' => 'display_name',
    'order' => 'ASC'
));
$users = $users_query->get_results();

// Obtener categorías disponibles
$settings = get_option('mhtp_ts_settings', array());
$categories = isset($settings['categories']) ? $settings['categories'] : array('test', 'promo', 'demo');

// Obtener instancia de la base de datos
$db = MHTP_TS_DB::get_instance();
?>

<div class="wrap mhtp-ts-admin-wrap">
    <h1 class="wp-heading-inline"><?php _e('Gestionar Sesiones de Prueba', 'mhtp-test-sessions'); ?></h1>
    
    <div class="mhtp-ts-admin-container">
        <div class="mhtp-ts-admin-box">
            <h2><?php _e('Añadir Sesiones', 'mhtp-test-sessions'); ?></h2>
            
            <form id="mhtp-ts-add-sessions-form" class="mhtp-ts-form" method="post" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
                <input type="hidden" name="action" value="mhtp_ts_add_sessions">
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('mhtp_ts_admin_nonce'); ?>">
                
                <div class="mhtp-ts-form-row">
                    <label for="mhtp-ts-user"><?php _e('Usuario', 'mhtp-test-sessions'); ?></label>
                    <select id="mhtp-ts-user" name="user_id" required>
                        <?php if ($selected_user): ?>
                            <option value="<?php echo esc_attr($selected_user->ID); ?>" selected>
                                <?php echo esc_html($selected_user->display_name); ?> (<?php echo esc_html($selected_user->user_email); ?>)
                            </option>
                        <?php else: ?>
                            <option value=""><?php _e('Seleccionar usuario', 'mhtp-test-sessions'); ?></option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo esc_attr($user->ID); ?>">
                                    <?php echo esc_html($user->display_name); ?> (<?php echo esc_html($user->user_email); ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="mhtp-ts-form-row">
                    <label for="mhtp-ts-quantity"><?php _e('Cantidad', 'mhtp-test-sessions'); ?></label>
                    <input type="number" id="mhtp-ts-quantity" name="quantity" min="1" value="1" required>
                </div>
                
                <div class="mhtp-ts-form-row">
                    <label for="mhtp-ts-category"><?php _e('Categoría', 'mhtp-test-sessions'); ?></label>
                    <select id="mhtp-ts-category" name="category">
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo esc_attr($category); ?>">
                                <?php echo esc_html(ucfirst($category)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mhtp-ts-form-row">
                    <label for="mhtp-ts-expiry"><?php _e('Caducidad (días)', 'mhtp-test-sessions'); ?></label>
                    <input type="number" id="mhtp-ts-expiry" name="expiry_days" min="1" value="30">
                    <p class="description"><?php _e('Días hasta que caduquen las sesiones. Dejar en blanco para no establecer caducidad.', 'mhtp-test-sessions'); ?></p>
                </div>
                
                <div class="mhtp-ts-form-row">
                    <label for="mhtp-ts-notes"><?php _e('Notas', 'mhtp-test-sessions'); ?></label>
                    <textarea id="mhtp-ts-notes" name="notes" rows="3"></textarea>
                </div>
                
                <div class="mhtp-ts-form-row">
                    <button type="submit" class="button button-primary"><?php _e('Añadir Sesiones', 'mhtp-test-sessions'); ?></button>
                </div>
            </form>
            
            <div id="mhtp-ts-add-result" class="mhtp-ts-result" style="display: none;"></div>
        </div>
        
        <?php if ($selected_user): ?>
            <div class="mhtp-ts-admin-box">
                <h2><?php printf(__('Sesiones de %s', 'mhtp-test-sessions'), $selected_user->display_name); ?></h2>
                
                <?php
                $sessions = $db->get_user_test_sessions($selected_user->ID);
                ?>
                
                <?php if ($sessions && count($sessions) > 0): ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('ID', 'mhtp-test-sessions'); ?></th>
                                <th><?php _e('Categoría', 'mhtp-test-sessions'); ?></th>
                                <th><?php _e('Cantidad', 'mhtp-test-sessions'); ?></th>
                                <th><?php _e('Fecha de Creación', 'mhtp-test-sessions'); ?></th>
                                <th><?php _e('Fecha de Caducidad', 'mhtp-test-sessions'); ?></th>
                                <th><?php _e('Estado', 'mhtp-test-sessions'); ?></th>
                                <th><?php _e('Acciones', 'mhtp-test-sessions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sessions as $session): ?>
                                <tr>
                                    <td><?php echo esc_html($session->id); ?></td>
                                    <td>
                                        <span class="mhtp-ts-category mhtp-ts-category-<?php echo esc_attr($session->category); ?>">
                                            <?php echo esc_html($session->category); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html($session->quantity); ?></td>
                                    <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($session->created_at))); ?></td>
                                    <td>
                                        <?php if ($session->expiry_date): ?>
                                            <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($session->expiry_date))); ?>
                                        <?php else: ?>
                                            <?php _e('Sin caducidad', 'mhtp-test-sessions'); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="mhtp-ts-status mhtp-ts-status-<?php echo esc_attr($session->status); ?>">
                                            <?php echo esc_html($session->status); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="button button-small mhtp-ts-delete-session" data-session-id="<?php echo esc_attr($session->id); ?>" data-nonce="<?php echo wp_create_nonce('mhtp_ts_admin_nonce'); ?>">
                                            <?php _e('Eliminar', 'mhtp-test-sessions'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p><?php _e('Este usuario no tiene sesiones de prueba activas.', 'mhtp-test-sessions'); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Lista de Usuarios Integrada -->
        <div class="mhtp-ts-admin-box">
            <h2><?php _e('Lista de Usuarios', 'mhtp-test-sessions'); ?></h2>
            
            <div class="mhtp-ts-filter-container">
                <form id="mhtp-ts-users-filter-form" class="mhtp-ts-form">
                    <div class="mhtp-ts-form-row mhtp-ts-filter-row">
                        <input type="text" id="mhtp-ts-users-search" placeholder="<?php _e('Buscar usuarios...', 'mhtp-test-sessions'); ?>">
                        
                        <select id="mhtp-ts-users-role">
                            <option value=""><?php _e('Todos los roles', 'mhtp-test-sessions'); ?></option>
                            <?php 
                            $roles = wp_roles()->get_names();
                            foreach ($roles as $role_key => $role_name): 
                            ?>
                                <option value="<?php echo esc_attr($role_key); ?>"><?php echo esc_html($role_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        
                        <select id="mhtp-ts-users-has-sessions">
                            <option value=""><?php _e('Todas las sesiones', 'mhtp-test-sessions'); ?></option>
                            <option value="yes"><?php _e('Con sesiones', 'mhtp-test-sessions'); ?></option>
                            <option value="no"><?php _e('Sin sesiones', 'mhtp-test-sessions'); ?></option>
                        </select>
                        
                        <button type="submit" class="button"><?php _e('Filtrar', 'mhtp-test-sessions'); ?></button>
                        <button type="button" id="mhtp-ts-users-reset" class="button"><?php _e('Reiniciar', 'mhtp-test-sessions'); ?></button>
                    </div>
                </form>
            </div>
            
            <div id="mhtp-ts-users-table-container">
                <table class="wp-list-table widefat fixed striped mhtp-ts-users-table">
                    <thead>
                        <tr>
                            <th class="column-username"><?php _e('Usuario', 'mhtp-test-sessions'); ?></th>
                            <th class="column-email"><?php _e('Email', 'mhtp-test-sessions'); ?></th>
                            <th class="column-role"><?php _e('Rol', 'mhtp-test-sessions'); ?></th>
                            <th class="column-registered"><?php _e('Registro', 'mhtp-test-sessions'); ?></th>
                            <th class="column-sessions"><?php _e('Sesiones', 'mhtp-test-sessions'); ?></th>
                            <th class="column-actions"><?php _e('Acciones', 'mhtp-test-sessions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): 
                            // Obtener roles del usuario
                            $user_roles = array();
                            if (!empty($user->roles) && is_array($user->roles)) {
                                foreach ($user->roles as $role) {
                                    if (isset($roles[$role])) {
                                        $user_roles[] = $roles[$role];
                                    } else {
                                        $user_roles[] = $role;
                                    }
                                }
                            }
                            $role_display = implode(', ', $user_roles);
                            
                            // Contar sesiones disponibles
                            $sessions_count = $db->count_user_available_sessions($user->ID);
                        ?>
                            <tr>
                                <td class="column-username">
                                    <strong><?php echo esc_html($user->display_name); ?></strong>
                                </td>
                                <td class="column-email"><?php echo esc_html($user->user_email); ?></td>
                                <td class="column-role"><?php echo esc_html($role_display); ?></td>
                                <td class="column-registered"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($user->user_registered))); ?></td>
                                <td class="column-sessions">
                                    <span class="mhtp-ts-sessions-count <?php echo $sessions_count > 0 ? 'has-sessions' : 'no-sessions'; ?>">
                                        <?php echo esc_html($sessions_count); ?>
                                    </span>
                                </td>
                                <td class="column-actions">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=mhtp-test-sessions&user_id=' . $user->ID)); ?>" class="button">
                                        <?php _e('Añadir Sesiones', 'mhtp-test-sessions'); ?>
                                    </a>
                                    
                                    <?php if ($sessions_count > 0): ?>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=mhtp-test-sessions&user_id=' . $user->ID)); ?>" class="button">
                                            <?php _e('Ver Sesiones', 'mhtp-test-sessions'); ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Manejar envío del formulario
    $('#mhtp-ts-add-sessions-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var submitBtn = form.find('button[type="submit"]');
        var resultDiv = $('#mhtp-ts-add-result');
        
        // Deshabilitar botón y mostrar mensaje de carga
        submitBtn.prop('disabled', true).text('<?php _e('Procesando...', 'mhtp-test-sessions'); ?>');
        resultDiv.html('<p class="mhtp-ts-loading"><?php _e('Añadiendo sesiones...', 'mhtp-test-sessions'); ?></p>').show();
        
        // Enviar solicitud AJAX
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            success: function(response) {
                // Habilitar botón
                submitBtn.prop('disabled', false).text('<?php _e('Añadir Sesiones', 'mhtp-test-sessions'); ?>');
                
                if (response.success) {
                    // Mostrar mensaje de éxito
                    resultDiv.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                    
                    // Recargar página después de 2 segundos
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    // Mostrar mensaje de error
                    resultDiv.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                }
            },
            error: function(xhr, status, error) {
                // Habilitar botón
                submitBtn.prop('disabled', false).text('<?php _e('Añadir Sesiones', 'mhtp-test-sessions'); ?>');
                
                // Mostrar mensaje de error
                resultDiv.html('<div class="notice notice-error"><p><?php _e('Ha ocurrido un error al procesar la solicitud. Por favor, inténtalo de nuevo.', 'mhtp-test-sessions'); ?></p></div>');
                
                // Registrar error en consola
                console.error('Error AJAX:', xhr.responseText);
            }
        });
    });
    
    // Manejar eliminación de sesión
    $('.mhtp-ts-delete-session').on('click', function() {
        if (confirm('<?php _e('¿Estás seguro de que deseas eliminar esta sesión?', 'mhtp-test-sessions'); ?>')) {
            var button = $(this);
            var sessionId = button.data('session-id');
            var nonce = button.data('nonce');
            var row = button.closest('tr');
            
            // Deshabilitar botón
            button.prop('disabled', true).text('<?php _e('Eliminando...', 'mhtp-test-sessions'); ?>');
            
            // Enviar solicitud AJAX
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'mhtp_ts_delete_session',
                    session_id: sessionId,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Eliminar fila
                        row.fadeOut(300, function() {
                            row.remove();
                            
                            // Verificar si no quedan sesiones
                            if ($('table tbody tr').length === 0) {
                                $('table').replaceWith('<p><?php _e('Este usuario no tiene sesiones de prueba activas.', 'mhtp-test-sessions'); ?></p>');
                            }
                        });
                    } else {
                        // Mostrar mensaje de error
                        alert(response.data.message);
                        button.prop('disabled', false).text('<?php _e('Eliminar', 'mhtp-test-sessions'); ?>');
                    }
                },
                error: function() {
                    // Mostrar mensaje de error
                    alert('<?php _e('Ha ocurrido un error al procesar la solicitud. Por favor, inténtalo de nuevo.', 'mhtp-test-sessions'); ?>');
                    button.prop('disabled', false).text('<?php _e('Eliminar', 'mhtp-test-sessions'); ?>');
                }
            });
        }
    });
    
    // Filtrar usuarios
    $('#mhtp-ts-users-filter-form').on('submit', function(e) {
        e.preventDefault();
        
        var search = $('#mhtp-ts-users-search').val().toLowerCase();
        var role = $('#mhtp-ts-users-role').val();
        var hasSessions = $('#mhtp-ts-users-has-sessions').val();
        
        // Filtrar filas de la tabla
        $('.mhtp-ts-users-table tbody tr').each(function() {
            var row = $(this);
            var username = row.find('.column-username').text().toLowerCase();
            var email = row.find('.column-email').text().toLowerCase();
            var userRole = row.find('.column-role').text().toLowerCase();
            var sessionsCount = parseInt(row.find('.column-sessions').text().trim());
            
            var showRow = true;
            
            // Filtrar por búsqueda
            if (search && !(username.includes(search) || email.includes(search))) {
                showRow = false;
            }
            
            // Filtrar por rol
            if (role && !userRole.includes(role.toLowerCase())) {
                showRow = false;
            }
            
            // Filtrar por sesiones
            if (hasSessions === 'yes' && sessionsCount <= 0) {
                showRow = false;
            } else if (hasSessions === 'no' && sessionsCount > 0) {
                showRow = false;
            }
            
            // Mostrar u ocultar fila
            if (showRow) {
                row.show();
            } else {
                row.hide();
            }
        });
    });
    
    // Reiniciar filtros
    $('#mhtp-ts-users-reset').on('click', function() {
        $('#mhtp-ts-users-search').val('');
        $('#mhtp-ts-users-role').val('');
        $('#mhtp-ts-users-has-sessions').val('');
        
        // Mostrar todas las filas
        $('.mhtp-ts-users-table tbody tr').show();
    });
});
</script>
