<?php
/**
 * Template for displaying current sessions.
 *
 * @package MHTP_Current_Sessions_V9
 */

// Exit if accessed directly.
if (!defined('WPINC')) {
    exit;
}
?>

<div class="mhtp-cs-container">
    <div class="mhtp-cs-header">
        <h2><?php _e('Mis Sesiones', 'mhtp-current-sessions-v9'); ?></h2>
    </div>
    
    <!-- Available Sessions Summary -->
    <div class="mhtp-cs-summary">
        <p>
            <?php 
            printf(
                _n(
                    'Tienes <strong>%d sesión disponible</strong>.', 
                    'Tienes <strong>%d sesiones disponibles</strong>.', 
                    $available_sessions, 
                    'mhtp-current-sessions-v9'
                ), 
                $available_sessions
            ); 
            ?>
        </p>
    </div>
    
    <!-- Purchase Sessions Section -->
    <div class="mhtp-cs-purchase-section">
        <h3 class="mhtp-cs-purchase-title"><?php _e('Adquirir Nuevas Sesiones', 'mhtp-current-sessions-v9'); ?></h3>
        <div class="mhtp-cs-purchase-options">
            <a href="https://gdotest.com/product/pase-1-sesion/" class="mhtp-cs-purchase-option">
                <div class="mhtp-cs-purchase-image mhtp-cs-purchase-1">
                    <span class="mhtp-cs-purchase-count">1</span>
                    <span class="mhtp-cs-purchase-label"><?php _e('Sesión', 'mhtp-current-sessions-v9'); ?></span>
                </div>
            </a>
            <a href="https://gdotest.com/product/pase-5-sesiones/" class="mhtp-cs-purchase-option">
                <div class="mhtp-cs-purchase-image mhtp-cs-purchase-5">
                    <span class="mhtp-cs-purchase-count">5</span>
                    <span class="mhtp-cs-purchase-label"><?php _e('Sesiones', 'mhtp-current-sessions-v9'); ?></span>
                </div>
            </a>
            <a href="https://gdotest.com/product/pase-10-sesiones/" class="mhtp-cs-purchase-option">
                <div class="mhtp-cs-purchase-image mhtp-cs-purchase-10">
                    <span class="mhtp-cs-purchase-count">10</span>
                    <span class="mhtp-cs-purchase-label"><?php _e('Sesiones', 'mhtp-current-sessions-v9'); ?></span>
                </div>
            </a>
        </div>
    </div>
    
    <!-- Session History Section -->
    <div class="mhtp-cs-history-section">
        <h3 class="mhtp-cs-history-title"><?php _e('Historial de Sesiones', 'mhtp-current-sessions-v9'); ?></h3>
        
        <?php if (empty($history_sessions)): ?>
        <div class="mhtp-cs-empty-history">
            <p><?php _e('No tienes sesiones en tu historial', 'mhtp-current-sessions-v9'); ?></p>
        </div>
        <?php else: ?>
        <div class="mhtp-cs-history-table-container">
            <table class="mhtp-cs-history-table">
                <thead>
                    <tr>
                        <th><?php _e('Fecha', 'mhtp-current-sessions-v9'); ?></th>
                        <th><?php _e('Experto', 'mhtp-current-sessions-v9'); ?></th>
                        <th><?php _e('Duración', 'mhtp-current-sessions-v9'); ?></th>
                        <th><?php _e('Resumen', 'mhtp-current-sessions-v9'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history_sessions as $history): ?>
                    <tr>
                        <td><?php echo date_i18n(get_option('date_format'), strtotime($history['date'])); ?></td>
                        <td><?php echo esc_html($history['expert_name']); ?></td>
                        <td><?php echo esc_html($history['duration']); ?></td>
                        <td>
                            <a href="#" class="mhtp-cs-button mhtp-cs-summary-button white-text-button" data-session-id="<?php echo esc_attr($history['id']); ?>">
                                <?php _e('Ver resumen', 'mhtp-current-sessions-v9'); ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
