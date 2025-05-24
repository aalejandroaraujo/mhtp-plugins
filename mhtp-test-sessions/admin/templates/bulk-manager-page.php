<?php
/**
 * Template para la página de asignación masiva
 *
 * @package MHTP_Test_Sessions
 */

// Si este archivo es llamado directamente, abortar
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap mhtp-ts-admin-wrap">
    <h1 class="wp-heading-inline"><?php _e('Asignación Masiva de Sesiones', 'mhtp-test-sessions'); ?></h1>
    
    <div class="mhtp-ts-admin-container">
        <div class="mhtp-ts-admin-tabs">
            <ul class="mhtp-ts-tabs-nav">
                <li class="active"><a href="#mhtp-ts-tab-filter"><?php _e('Filtrar Usuarios', 'mhtp-test-sessions'); ?></a></li>
                <li><a href="#mhtp-ts-tab-csv"><?php _e('Importar CSV', 'mhtp-test-sessions'); ?></a></li>
            </ul>
            
            <div class="mhtp-ts-tabs-content">
                <!-- Pestaña de filtrado -->
                <div id="mhtp-ts-tab-filter" class="mhtp-ts-tab-content active">
                    <div class="mhtp-ts-admin-box">
                        <h2><?php _e('Filtrar Usuarios', 'mhtp-test-sessions'); ?></h2>
                        
                        <form id="mhtp-ts-filter-users-form" class="mhtp-ts-form">
                            <div class="mhtp-ts-form-row mhtp-ts-form-row-inline">
                                <div class="mhtp-ts-form-col">
                                    <label for="mhtp-ts-role-filter"><?php _e('Rol', 'mhtp-test-sessions'); ?></label>
                                    <select id="mhtp-ts-role-filter" name="role">
                                        <option value=""><?php _e('Todos los roles', 'mhtp-test-sessions'); ?></option>
                                        <?php foreach ($roles as $role_key => $role_name): ?>
                                            <option value="<?php echo esc_attr($role_key); ?>">
                                                <?php echo esc_html($role_name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mhtp-ts-form-col">
                                    <label for="mhtp-ts-registration-date"><?php _e('Registrados después de', 'mhtp-test-sessions'); ?></label>
                                    <input type="date" id="mhtp-ts-registration-date" name="registration_date">
                                </div>
                                
                                <div class="mhtp-ts-form-col">
                                    <label for="mhtp-ts-has-purchased"><?php _e('Ha realizado compras', 'mhtp-test-sessions'); ?></label>
                                    <select id="mhtp-ts-has-purchased" name="has_purchased">
                                        <option value=""><?php _e('Cualquiera', 'mhtp-test-sessions'); ?></option>
                                        <option value="1"><?php _e('Sí', 'mhtp-test-sessions'); ?></option>
                                        <option value="0"><?php _e('No', 'mhtp-test-sessions'); ?></option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mhtp-ts-form-row">
                                <label for="mhtp-ts-search"><?php _e('Buscar', 'mhtp-test-sessions'); ?></label>
                                <input type="text" id="mhtp-ts-search" name="search" placeholder="<?php esc_attr_e('Nombre o email', 'mhtp-test-sessions'); ?>">
                            </div>
                            
                            <div class="mhtp-ts-form-row">
                                <button type="submit" class="button button-primary"><?php _e('Filtrar Usuarios', 'mhtp-test-sessions'); ?></button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="mhtp-ts-admin-box">
                        <h2><?php _e('Usuarios Filtrados', 'mhtp-test-sessions'); ?></h2>
                        
                        <div id="mhtp-ts-filtered-users-container">
                            <p class="mhtp-ts-filter-message">
                                <?php _e('Utiliza los filtros para buscar usuarios', 'mhtp-test-sessions'); ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Pestaña de importación CSV -->
                <div id="mhtp-ts-tab-csv" class="mhtp-ts-tab-content">
                    <div class="mhtp-ts-admin-box">
                        <h2><?php _e('Importar Usuarios desde CSV', 'mhtp-test-sessions'); ?></h2>
                        
                        <form id="mhtp-ts-csv-form" class="mhtp-ts-form" enctype="multipart/form-data">
                            <div class="mhtp-ts-form-row">
                                <label for="mhtp-ts-csv-file"><?php _e('Archivo CSV', 'mhtp-test-sessions'); ?></label>
                                <input type="file" id="mhtp-ts-csv-file" name="csv_file" accept=".csv">
                                <p class="description">
                                    <?php _e('El archivo CSV debe contener una columna "email" o "user_id" para identificar a los usuarios.', 'mhtp-test-sessions'); ?>
                                </p>
                            </div>
                            
                            <div class="mhtp-ts-form-row">
                                <button type="submit" class="button button-primary"><?php _e('Procesar CSV', 'mhtp-test-sessions'); ?></button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="mhtp-ts-admin-box">
                        <h2><?php _e('Usuarios Importados', 'mhtp-test-sessions'); ?></h2>
                        
                        <div id="mhtp-ts-imported-users-container">
                            <p class="mhtp-ts-import-message">
                                <?php _e('Sube un archivo CSV para importar usuarios', 'mhtp-test-sessions'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mhtp-ts-admin-box mhtp-ts-bulk-assign-box" style="display: none;">
            <h2><?php _e('Asignar Sesiones a Usuarios Seleccionados', 'mhtp-test-sessions'); ?></h2>
            
            <form id="mhtp-ts-bulk-assign-form" class="mhtp-ts-form">
                <input type="hidden" id="mhtp-ts-selected-users" name="user_ids" value="">
                
                <div class="mhtp-ts-form-row mhtp-ts-form-row-inline">
                    <div class="mhtp-ts-form-col">
                        <label for="mhtp-ts-bulk-quantity"><?php _e('Cantidad', 'mhtp-test-sessions'); ?></label>
                        <input type="number" id="mhtp-ts-bulk-quantity" name="quantity" min="1" value="1" required>
                    </div>
                    
                    <div class="mhtp-ts-form-col">
                        <label for="mhtp-ts-bulk-category"><?php _e('Categoría', 'mhtp-test-sessions'); ?></label>
                        <select id="mhtp-ts-bulk-category" name="category">
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo esc_attr($category); ?>">
                                    <?php echo esc_html(ucfirst($category)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mhtp-ts-form-col">
                        <label for="mhtp-ts-bulk-expiry"><?php _e('Caducidad (días)', 'mhtp-test-sessions'); ?></label>
                        <input type="number" id="mhtp-ts-bulk-expiry" name="expiry_days" min="1" value="30">
                    </div>
                </div>
                
                <div class="mhtp-ts-form-row">
                    <label for="mhtp-ts-bulk-notes"><?php _e('Notas', 'mhtp-test-sessions'); ?></label>
                    <textarea id="mhtp-ts-bulk-notes" name="notes" rows="3"></textarea>
                </div>
                
                <div class="mhtp-ts-form-row">
                    <button type="submit" class="button button-primary"><?php _e('Asignar Sesiones', 'mhtp-test-sessions'); ?></button>
                    <span id="mhtp-ts-selected-count" class="mhtp-ts-selected-count"></span>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/template" id="mhtp-ts-filtered-users-template">
    <# if (users.length > 0) { #>
        <div class="mhtp-ts-bulk-actions">
            <button id="mhtp-ts-select-all" class="button"><?php _e('Seleccionar Todos', 'mhtp-test-sessions'); ?></button>
            <button id="mhtp-ts-deselect-all" class="button"><?php _e('Deseleccionar Todos', 'mhtp-test-sessions'); ?></button>
        </div>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th class="check-column">
                        <input type="checkbox" id="mhtp-ts-users-check-all">
                    </th>
                    <th><?php _e('Usuario', 'mhtp-test-sessions'); ?></th>
                    <th><?php _e('Email', 'mhtp-test-sessions'); ?></th>
                    <th><?php _e('Fecha de Registro', 'mhtp-test-sessions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <# _.each(users, function(user) { #>
                    <tr>
                        <td class="check-column">
                            <input type="checkbox" class="mhtp-ts-user-checkbox" value="{{ user.id }}">
                        </td>
                        <td>{{ user.display_name }}</td>
                        <td>{{ user.email }}</td>
                        <td>{{ user.registered }}</td>
                    </tr>
                <# }); #>
            </tbody>
        </table>
        
        <div class="mhtp-ts-bulk-actions">
            <button id="mhtp-ts-show-bulk-assign" class="button button-primary"><?php _e('Asignar Sesiones a Seleccionados', 'mhtp-test-sessions'); ?></button>
        </div>
    <# } else { #>
        <p class="mhtp-ts-no-users">
            <?php _e('No se encontraron usuarios con los filtros seleccionados.', 'mhtp-test-sessions'); ?>
        </p>
    <# } #>
</script>

<script type="text/template" id="mhtp-ts-imported-users-template">
    <# if (users.length > 0) { #>
        <div class="mhtp-ts-bulk-actions">
            <button id="mhtp-ts-select-all-imported" class="button"><?php _e('Seleccionar Todos', 'mhtp-test-sessions'); ?></button>
            <button id="mhtp-ts-deselect-all-imported" class="button"><?php _e('Deseleccionar Todos', 'mhtp-test-sessions'); ?></button>
        </div>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th class="check-column">
                        <input type="checkbox" id="mhtp-ts-imported-check-all">
                    </th>
                    <th><?php _e('Usuario', 'mhtp-test-sessions'); ?></th>
                    <th><?php _e('Email', 'mhtp-test-sessions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <# _.each(users, function(user) { #>
                    <tr>
                        <td class="check-column">
                            <input type="checkbox" class="mhtp-ts-user-checkbox" value="{{ user.id }}">
                        </td>
                        <td>{{ user.display_name }}</td>
                        <td>{{ user.email }}</td>
                    </tr>
                <# }); #>
            </tbody>
        </table>
        
        <div class="mhtp-ts-bulk-actions">
            <button id="mhtp-ts-show-bulk-assign-imported" class="button button-primary"><?php _e('Asignar Sesiones a Seleccionados', 'mhtp-test-sessions'); ?></button>
        </div>
        
        <# if (errors && errors.length > 0) { #>
            <div class="mhtp-ts-import-errors">
                <h3><?php _e('Errores de Importación', 'mhtp-test-sessions'); ?></h3>
                <ul>
                    <# _.each(errors, function(error) { #>
                        <li>{{ error }}</li>
                    <# }); #>
                </ul>
            </div>
        <# } #>
    <# } else { #>
        <p class="mhtp-ts-no-users">
            <?php _e('No se encontraron usuarios en el archivo CSV.', 'mhtp-test-sessions'); ?>
        </p>
    <# } #>
</script>
