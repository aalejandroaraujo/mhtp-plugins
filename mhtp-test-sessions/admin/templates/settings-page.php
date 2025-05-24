<?php
/**
 * Template para la página de configuración
 *
 * @package MHTP_Test_Sessions
 */

// Si este archivo es llamado directamente, abortar
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap mhtp-ts-admin-wrap">
    <h1 class="wp-heading-inline"><?php _e('Configuración de Sesiones de Prueba', 'mhtp-test-sessions'); ?></h1>
    
    <div class="mhtp-ts-admin-container">
        <form method="post" action="options.php" class="mhtp-ts-form">
            <?php settings_fields('mhtp_ts_settings'); ?>
            
            <div class="mhtp-ts-admin-box">
                <h2><?php _e('Configuración General', 'mhtp-test-sessions'); ?></h2>
                
                <div class="mhtp-ts-form-row">
                    <label for="mhtp_ts_settings_default_expiry_days"><?php _e('Días de caducidad por defecto', 'mhtp-test-sessions'); ?></label>
                    <input type="number" id="mhtp_ts_settings_default_expiry_days" name="mhtp_ts_settings[default_expiry_days]" min="1" value="<?php echo esc_attr($settings['default_expiry_days']); ?>">
                    <p class="description"><?php _e('Número de días hasta que caduquen las sesiones de prueba por defecto.', 'mhtp-test-sessions'); ?></p>
                </div>
                
                <div class="mhtp-ts-form-row">
                    <label for="mhtp_ts_settings_default_category"><?php _e('Categoría por defecto', 'mhtp-test-sessions'); ?></label>
                    <select id="mhtp_ts_settings_default_category" name="mhtp_ts_settings[default_category]">
                        <?php foreach ($settings['categories'] as $category): ?>
                            <option value="<?php echo esc_attr($category); ?>" <?php selected($settings['default_category'], $category); ?>>
                                <?php echo esc_html(ucfirst($category)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php _e('Categoría por defecto para nuevas sesiones de prueba.', 'mhtp-test-sessions'); ?></p>
                </div>
            </div>
            
            <div class="mhtp-ts-admin-box">
                <h2><?php _e('Categorías de Sesiones', 'mhtp-test-sessions'); ?></h2>
                
                <div class="mhtp-ts-form-row">
                    <label><?php _e('Categorías disponibles', 'mhtp-test-sessions'); ?></label>
                    
                    <div id="mhtp-ts-categories-container">
                        <?php foreach ($settings['categories'] as $index => $category): ?>
                            <div class="mhtp-ts-category-item">
                                <input type="text" name="mhtp_ts_settings[categories][]" value="<?php echo esc_attr($category); ?>">
                                <button type="button" class="button mhtp-ts-remove-category"><?php _e('Eliminar', 'mhtp-test-sessions'); ?></button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <button type="button" id="mhtp-ts-add-category" class="button"><?php _e('Añadir Categoría', 'mhtp-test-sessions'); ?></button>
                    <p class="description"><?php _e('Define las categorías disponibles para clasificar las sesiones de prueba.', 'mhtp-test-sessions'); ?></p>
                </div>
            </div>
            
            <div class="mhtp-ts-admin-box">
                <h2><?php _e('Integración con WooCommerce', 'mhtp-test-sessions'); ?></h2>
                
                <div class="mhtp-ts-form-row">
                    <label><?php _e('IDs de productos de sesiones', 'mhtp-test-sessions'); ?></label>
                    
                    <div id="mhtp-ts-products-container">
                        <?php foreach ($settings['woocommerce_product_ids'] as $product_id): ?>
                            <div class="mhtp-ts-product-item">
                                <input type="number" name="mhtp_ts_settings[woocommerce_product_ids][]" value="<?php echo esc_attr($product_id); ?>">
                                <button type="button" class="button mhtp-ts-remove-product"><?php _e('Eliminar', 'mhtp-test-sessions'); ?></button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <button type="button" id="mhtp-ts-add-product" class="button"><?php _e('Añadir Producto', 'mhtp-test-sessions'); ?></button>
                    <p class="description"><?php _e('IDs de productos de WooCommerce que representan sesiones. Actualmente: 483 (1 sesión), 486 (5 sesiones), 487 (10 sesiones).', 'mhtp-test-sessions'); ?></p>
                </div>
            </div>
            
            <div class="mhtp-ts-admin-box">
                <h2><?php _e('Apariencia', 'mhtp-test-sessions'); ?></h2>
                
                <div class="mhtp-ts-form-row">
                    <label for="mhtp_ts_settings_primary_color"><?php _e('Color principal', 'mhtp-test-sessions'); ?></label>
                    <input type="color" id="mhtp_ts_settings_primary_color" name="mhtp_ts_settings[primary_color]" value="<?php echo esc_attr(isset($settings['primary_color']) ? $settings['primary_color'] : '#3a5998'); ?>">
                    <p class="description"><?php _e('Color principal para elementos de la interfaz.', 'mhtp-test-sessions'); ?></p>
                </div>
                
                <div class="mhtp-ts-form-row">
                    <label for="mhtp_ts_settings_secondary_color"><?php _e('Color secundario', 'mhtp-test-sessions'); ?></label>
                    <input type="color" id="mhtp_ts_settings_secondary_color" name="mhtp_ts_settings[secondary_color]" value="<?php echo esc_attr(isset($settings['secondary_color']) ? $settings['secondary_color'] : '#4CAF50'); ?>">
                    <p class="description"><?php _e('Color secundario para elementos de la interfaz.', 'mhtp-test-sessions'); ?></p>
                </div>
            </div>
            
            <div class="mhtp-ts-form-row">
                <?php submit_button(__('Guardar Configuración', 'mhtp-test-sessions'), 'primary', 'submit', false); ?>
            </div>
        </form>
    </div>
</div>

<script type="text/template" id="mhtp-ts-category-template">
    <div class="mhtp-ts-category-item">
        <input type="text" name="mhtp_ts_settings[categories][]" value="">
        <button type="button" class="button mhtp-ts-remove-category"><?php _e('Eliminar', 'mhtp-test-sessions'); ?></button>
    </div>
</script>

<script type="text/template" id="mhtp-ts-product-template">
    <div class="mhtp-ts-product-item">
        <input type="number" name="mhtp_ts_settings[woocommerce_product_ids][]" value="">
        <button type="button" class="button mhtp-ts-remove-product"><?php _e('Eliminar', 'mhtp-test-sessions'); ?></button>
    </div>
</script>
