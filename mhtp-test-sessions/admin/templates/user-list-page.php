<?php
/**
 * Template para la página de lista de usuarios
 *
 * @package MHTP_Test_Sessions
 */

// Si este archivo es llamado directamente, abortar
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap mhtp-ts-admin-wrap">
    <h1 class="wp-heading-inline"><?php _e('Lista de Usuarios', 'mhtp-test-sessions'); ?></h1>
    
    <div class="mhtp-ts-admin-container">
        <div class="mhtp-ts-admin-box mhtp-ts-users-filters">
            <h2><?php _e('Filtrar Usuarios', 'mhtp-test-sessions'); ?></h2>
            
            <form id="mhtp-ts-users-filter-form" class="mhtp-ts-form">
                <div class="mhtp-ts-form-row mhtp-ts-form-row-inline">
                    <div class="mhtp-ts-form-col">
                        <label for="mhtp-ts-users-search"><?php _e('Buscar', 'mhtp-test-sessions'); ?></label>
                        <input type="text" id="mhtp-ts-users-search" placeholder="<?php esc_attr_e('Nombre o email', 'mhtp-test-sessions'); ?>">
                    </div>
                    
                    <div class="mhtp-ts-form-col">
                        <label for="mhtp-ts-users-role"><?php _e('Rol', 'mhtp-test-sessions'); ?></label>
                        <select id="mhtp-ts-users-role">
                            <option value=""><?php _e('Todos los roles', 'mhtp-test-sessions'); ?></option>
                            <?php foreach ($roles as $role_key => $role_name): ?>
                                <option value="<?php echo esc_attr($role_key); ?>">
                                    <?php echo esc_html($role_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mhtp-ts-form-col">
                        <label for="mhtp-ts-users-has-sessions"><?php _e('Sesiones', 'mhtp-test-sessions'); ?></label>
                        <select id="mhtp-ts-users-has-sessions">
                            <option value=""><?php _e('Todos', 'mhtp-test-sessions'); ?></option>
                            <option value="1"><?php _e('Con sesiones', 'mhtp-test-sessions'); ?></option>
                            <option value="0"><?php _e('Sin sesiones', 'mhtp-test-sessions'); ?></option>
                        </select>
                    </div>
                    
                    <div class="mhtp-ts-form-col">
                        <button type="submit" class="button button-primary"><?php _e('Filtrar', 'mhtp-test-sessions'); ?></button>
                        <button type="button" id="mhtp-ts-users-reset" class="button"><?php _e('Reiniciar', 'mhtp-test-sessions'); ?></button>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="mhtp-ts-admin-box">
            <h2><?php _e('Usuarios', 'mhtp-test-sessions'); ?></h2>
            
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
                            $user_data = get_userdata($user->ID);
                            $roles = $user_data->roles;
                            $role_names = array();
                            
                            foreach ($roles as $role) {
                                if (isset($wp_roles->role_names[$role])) {
                                    $role_names[] = translate_user_role($wp_roles->role_names[$role]);
                                }
                            }
                            
                            $sessions_count = get_user_meta($user->ID, 'mhtp_available_sessions', true);
                            if (empty($sessions_count)) {
                                $sessions_count = 0;
                            }
                        ?>
                        <tr>
                            <td class="column-username">
                                <strong><?php echo esc_html($user->display_name); ?></strong>
                            </td>
                            <td class="column-email">
                                <?php echo esc_html($user->user_email); ?>
                            </td>
                            <td class="column-role">
                                <?php echo esc_html(implode(', ', $role_names)); ?>
                            </td>
                            <td class="column-registered">
                                <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($user->user_registered))); ?>
                            </td>
                            <td class="column-sessions">
                                <span class="mhtp-ts-sessions-count <?php echo $sessions_count > 0 ? 'has-sessions' : 'no-sessions'; ?>">
                                    <?php echo esc_html($sessions_count); ?>
                                </span>
                            </td>
                            <td class="column-actions">
                                <button type="button" class="button mhtp-ts-add-sessions-btn" data-user-id="<?php echo esc_attr($user->ID); ?>" data-user-name="<?php echo esc_attr($user->display_name); ?>">
                                    <?php _e('Añadir Sesiones', 'mhtp-test-sessions'); ?>
                                </button>
                                
                                <?php if ($sessions_count > 0): ?>
                                <button type="button" class="button mhtp-ts-view-sessions-btn" data-user-id="<?php echo esc_attr($user->ID); ?>" data-user-name="<?php echo esc_attr($user->display_name); ?>">
                                    <?php _e('Ver Sesiones', 'mhtp-test-sessions'); ?>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="mhtp-ts-pagination">
                    <?php echo $pagination; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para añadir sesiones -->
<div id="mhtp-ts-add-sessions-modal" class="mhtp-ts-modal">
    <div class="mhtp-ts-modal-content">
        <span class="mhtp-ts-modal-close">&times;</span>
        
        <h2><?php _e('Añadir Sesiones', 'mhtp-test-sessions'); ?></h2>
        
        <div id="mhtp-ts-modal-user-info"></div>
        
        <form id="mhtp-ts-modal-add-sessions-form" class="mhtp-ts-form">
            <input type="hidden" id="mhtp-ts-modal-user-id" name="user_id" value="">
            
            <div class="mhtp-ts-form-row">
                <label for="mhtp-ts-modal-quantity"><?php _e('Cantidad', 'mhtp-test-sessions'); ?></label>
                <input type="number" id="mhtp-ts-modal-quantity" name="quantity" min="1" value="1" required>
            </div>
            
            <div class="mhtp-ts-form-row">
                <label for="mhtp-ts-modal-category"><?php _e('Categoría', 'mhtp-test-sessions'); ?></label>
                <select id="mhtp-ts-modal-category" name="category">
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo esc_attr($category); ?>">
                            <?php echo esc_html(ucfirst($category)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mhtp-ts-form-row">
                <label for="mhtp-ts-modal-expiry"><?php _e('Caducidad (días)', 'mhtp-test-sessions'); ?></label>
                <input type="number" id="mhtp-ts-modal-expiry" name="expiry_days" min="1" value="30">
                <p class="description"><?php _e('Días hasta que caduquen las sesiones. Dejar en blanco para no establecer caducidad.', 'mhtp-test-sessions'); ?></p>
            </div>
            
            <div class="mhtp-ts-form-row">
                <label for="mhtp-ts-modal-notes"><?php _e('Notas', 'mhtp-test-sessions'); ?></label>
                <textarea id="mhtp-ts-modal-notes" name="notes" rows="3"></textarea>
            </div>
            
            <div class="mhtp-ts-form-row">
                <button type="submit" class="button button-primary"><?php _e('Añadir Sesiones', 'mhtp-test-sessions'); ?></button>
                <button type="button" class="button mhtp-ts-modal-cancel"><?php _e('Cancelar', 'mhtp-test-sessions'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Modal para ver sesiones -->
<div id="mhtp-ts-view-sessions-modal" class="mhtp-ts-modal">
    <div class="mhtp-ts-modal-content">
        <span class="mhtp-ts-modal-close">&times;</span>
        
        <h2><?php _e('Sesiones del Usuario', 'mhtp-test-sessions'); ?></h2>
        
        <div id="mhtp-ts-modal-sessions-user-info"></div>
        
        <div id="mhtp-ts-modal-sessions-container">
            <p class="mhtp-ts-loading"><?php _e('Cargando sesiones...', 'mhtp-test-sessions'); ?></p>
        </div>
    </div>
</div>

<script type="text/template" id="mhtp-ts-user-info-template">
    <div class="mhtp-ts-user-info">
        <h3>{{ display_name }}</h3>
        <p>{{ email }}</p>
    </div>
</script>

<script type="text/template" id="mhtp-ts-sessions-template">
    <# if (sessions.length > 0) { #>
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
                <# _.each(sessions, function(session) { #>
                    <tr>
                        <td>{{ session.id }}</td>
                        <td>
                            <span class="mhtp-ts-category mhtp-ts-category-{{ session.category }}">
                                {{ session.category }}
                            </span>
                        </td>
                        <td>{{ session.quantity }}</td>
                        <td>{{ session.created_at }}</td>
                        <td>
                            <# if (session.expiry_date) { #>
                                {{ session.expiry_date }}
                            <# } else { #>
                                <?php _e('Sin caducidad', 'mhtp-test-sessions'); ?>
                            <# } #>
                        </td>
                        <td>
                            <span class="mhtp-ts-status mhtp-ts-status-{{ session.status }}">
                                {{ session.status }}
                            </span>
                        </td>
                        <td>
                            <button class="button button-small mhtp-ts-delete-session" data-session-id="{{ session.id }}">
                                <?php _e('Eliminar', 'mhtp-test-sessions'); ?>
                            </button>
                        </td>
                    </tr>
                <# }); #>
            </tbody>
        </table>
    <# } else { #>
        <p class="mhtp-ts-no-sessions">
            <?php _e('Este usuario no tiene sesiones de prueba activas.', 'mhtp-test-sessions'); ?>
        </p>
    <# } #>
</script>
