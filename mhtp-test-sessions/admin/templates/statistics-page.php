<?php
/**
 * Template para la página de estadísticas
 *
 * @package MHTP_Test_Sessions
 */

// Si este archivo es llamado directamente, abortar
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap mhtp-ts-admin-wrap">
    <h1 class="wp-heading-inline"><?php _e('Estadísticas de Sesiones de Prueba', 'mhtp-test-sessions'); ?></h1>
    
    <div class="mhtp-ts-admin-container">
        <div class="mhtp-ts-admin-box mhtp-ts-stats-filters">
            <form id="mhtp-ts-stats-filter-form" class="mhtp-ts-form">
                <div class="mhtp-ts-form-row mhtp-ts-form-row-inline">
                    <div class="mhtp-ts-form-col">
                        <label for="mhtp-ts-stats-period"><?php _e('Período', 'mhtp-test-sessions'); ?></label>
                        <select id="mhtp-ts-stats-period" name="period">
                            <option value="week"><?php _e('Última Semana', 'mhtp-test-sessions'); ?></option>
                            <option value="month" selected><?php _e('Último Mes', 'mhtp-test-sessions'); ?></option>
                            <option value="year"><?php _e('Último Año', 'mhtp-test-sessions'); ?></option>
                        </select>
                    </div>
                    
                    <div class="mhtp-ts-form-col">
                        <button type="submit" class="button button-primary"><?php _e('Actualizar Estadísticas', 'mhtp-test-sessions'); ?></button>
                    </div>
                    
                    <div class="mhtp-ts-form-col">
                        <button type="button" id="mhtp-ts-export-stats" class="button"><?php _e('Exportar a CSV', 'mhtp-test-sessions'); ?></button>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="mhtp-ts-stats-dashboard">
            <div class="mhtp-ts-admin-row">
                <!-- Métricas generales -->
                <div class="mhtp-ts-admin-col">
                    <div class="mhtp-ts-admin-box mhtp-ts-stats-box">
                        <h2><?php _e('Métricas Generales', 'mhtp-test-sessions'); ?></h2>
                        
                        <div class="mhtp-ts-stats-grid">
                            <div class="mhtp-ts-stat-card mhtp-ts-stat-primary">
                                <div class="mhtp-ts-stat-icon">
                                    <span class="dashicons dashicons-tickets-alt"></span>
                                </div>
                                <div class="mhtp-ts-stat-content">
                                    <div class="mhtp-ts-stat-value" id="mhtp-ts-total-sessions-added">
                                        <?php echo esc_html($stats['total_sessions_added'] ?: 0); ?>
                                    </div>
                                    <div class="mhtp-ts-stat-label">
                                        <?php _e('Sesiones Añadidas', 'mhtp-test-sessions'); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mhtp-ts-stat-card mhtp-ts-stat-success">
                                <div class="mhtp-ts-stat-icon">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                </div>
                                <div class="mhtp-ts-stat-content">
                                    <div class="mhtp-ts-stat-value" id="mhtp-ts-total-sessions-used">
                                        <?php echo esc_html($stats['total_sessions_used'] ?: 0); ?>
                                    </div>
                                    <div class="mhtp-ts-stat-label">
                                        <?php _e('Sesiones Usadas', 'mhtp-test-sessions'); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mhtp-ts-stat-card mhtp-ts-stat-warning">
                                <div class="mhtp-ts-stat-icon">
                                    <span class="dashicons dashicons-calendar-alt"></span>
                                </div>
                                <div class="mhtp-ts-stat-content">
                                    <div class="mhtp-ts-stat-value" id="mhtp-ts-total-sessions-expired">
                                        <?php echo esc_html($stats['total_sessions_expired'] ?: 0); ?>
                                    </div>
                                    <div class="mhtp-ts-stat-label">
                                        <?php _e('Sesiones Caducadas', 'mhtp-test-sessions'); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mhtp-ts-stat-card mhtp-ts-stat-info">
                                <div class="mhtp-ts-stat-icon">
                                    <span class="dashicons dashicons-groups"></span>
                                </div>
                                <div class="mhtp-ts-stat-content">
                                    <div class="mhtp-ts-stat-value" id="mhtp-ts-total-users">
                                        <?php echo esc_html($stats['total_users_with_sessions'] ?: 0); ?>
                                    </div>
                                    <div class="mhtp-ts-stat-label">
                                        <?php _e('Usuarios con Sesiones', 'mhtp-test-sessions'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tasa de conversión -->
                <div class="mhtp-ts-admin-col">
                    <div class="mhtp-ts-admin-box mhtp-ts-stats-box">
                        <h2><?php _e('Tasa de Conversión', 'mhtp-test-sessions'); ?></h2>
                        
                        <div class="mhtp-ts-conversion-container">
                            <div class="mhtp-ts-conversion-chart">
                                <canvas id="mhtp-ts-conversion-chart"></canvas>
                                <div class="mhtp-ts-conversion-rate">
                                    <span id="mhtp-ts-conversion-value"><?php echo number_format($stats['conversion_rate'], 1); ?></span>%
                                </div>
                            </div>
                            <div class="mhtp-ts-conversion-info">
                                <p>
                                    <?php _e('Porcentaje de usuarios que compraron sesiones después de usar sesiones gratuitas.', 'mhtp-test-sessions'); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mhtp-ts-admin-row">
                <!-- Gráfico de uso -->
                <div class="mhtp-ts-admin-col mhtp-ts-admin-col-full">
                    <div class="mhtp-ts-admin-box mhtp-ts-stats-box">
                        <h2><?php _e('Uso de Sesiones', 'mhtp-test-sessions'); ?></h2>
                        
                        <div class="mhtp-ts-usage-chart-container">
                            <canvas id="mhtp-ts-usage-chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mhtp-ts-admin-row">
                <!-- Uso por categoría -->
                <div class="mhtp-ts-admin-col">
                    <div class="mhtp-ts-admin-box mhtp-ts-stats-box">
                        <h2><?php _e('Uso por Categoría', 'mhtp-test-sessions'); ?></h2>
                        
                        <div class="mhtp-ts-category-chart-container">
                            <canvas id="mhtp-ts-category-chart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Tabla de categorías -->
                <div class="mhtp-ts-admin-col">
                    <div class="mhtp-ts-admin-box mhtp-ts-stats-box">
                        <h2><?php _e('Detalles por Categoría', 'mhtp-test-sessions'); ?></h2>
                        
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Categoría', 'mhtp-test-sessions'); ?></th>
                                    <th><?php _e('Sesiones', 'mhtp-test-sessions'); ?></th>
                                    <th><?php _e('Porcentaje', 'mhtp-test-sessions'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="mhtp-ts-category-table">
                                <?php 
                                $total = array_sum($stats['usage_by_category']);
                                foreach ($stats['usage_by_category'] as $category => $count): 
                                    $percentage = $total > 0 ? ($count / $total) * 100 : 0;
                                ?>
                                <tr>
                                    <td>
                                        <span class="mhtp-ts-category mhtp-ts-category-<?php echo esc_attr($category); ?>">
                                            <?php echo esc_html(ucfirst($category)); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html($count); ?></td>
                                    <td><?php echo number_format($percentage, 1); ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php if (empty($stats['usage_by_category'])): ?>
                                <tr>
                                    <td colspan="3"><?php _e('No hay datos disponibles', 'mhtp-test-sessions'); ?></td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
