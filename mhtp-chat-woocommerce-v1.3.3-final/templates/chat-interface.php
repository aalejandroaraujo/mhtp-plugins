<?php
/**
 * Chat interface template
 *
 * @package MHTP_Chat_Interface
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="mhtp-chat-container">
    <div class="mhtp-chat-header">
        <h2 class="mhtp-chat-title">Chat con Expertos</h2>
        <a href="<?php echo esc_url(remove_query_arg('expert_id', get_permalink())); ?>" class="mhtp-back-button">← Volver a selección de expertos</a>
    </div>
    
    <div class="mhtp-chat-content">
        <div class="mhtp-expert-sidebar">
            <?php if (!empty($expert)) : ?>
                <div class="mhtp-expert-avatar">
                    <img src="<?php echo esc_url($expert['avatar']); ?>" alt="<?php echo esc_attr($expert['name']); ?>">
                </div>
                <div class="mhtp-expert-info">
                    <h3 class="mhtp-expert-name"><?php 
                        // Extract just the name without any specialty or description
                        $full_name = $expert['name'];
                        
                        // Special case for Lucía Apoyo
                        if (strpos($full_name, 'Lucía Apoyo') !== false) {
                            echo 'Lucía Apoyo';
                        } else {
                            // Handle different types of separators (dash, hyphen, en dash, em dash)
                            $name_parts = preg_split('/\s*[-–—]\s*/', $full_name, 2);
                            
                            // If we have parts, use the first one, otherwise use the full name
                            $clean_name = !empty($name_parts[0]) ? trim($name_parts[0]) : trim($full_name);
                            
                            // Remove any "Experto en", "Experta en", "Especialista en" prefixes
                            $clean_name = preg_replace('/^(Experto|Experta|Especialista)\s+en\s+/i', '', $clean_name);
                            
                            echo esc_html($clean_name);
                        }
                    ?></h3>
                    <div class="mhtp-expert-specialty"><?php echo esc_html($expert['specialty']); ?></div>
                </div>
                <div class="mhtp-session-info">
                    <div class="mhtp-session-detail">
                        <span class="mhtp-session-label">ID de Sesión:</span>
                        <span class="mhtp-session-value"><?php echo esc_html(substr(md5(time() . $expert['id'] . get_current_user_id()), 0, 8)); ?></span>
                    </div>
                    <div class="mhtp-session-detail">
                        <span class="mhtp-session-label">Duración:</span>
                        <span class="mhtp-session-value">45 minutos</span>
                    </div>
                    <div class="mhtp-session-detail">
                        <span class="mhtp-session-label">Estado:</span>
                        <span class="mhtp-session-value mhtp-session-active">Activa</span>
                    </div>
                </div>
                
                <!-- Conversation saving options -->
                <div class="mhtp-conversation-options">
                    <div class="mhtp-conversation-option">
                        <label for="mhtp-save-conversation">
                            <input type="checkbox" id="mhtp-save-conversation" checked>
                            Guardar resumen online
                        </label>
                    </div>
                    <!-- Descarga deshabilitada hasta integrar almacenamiento -->
                    <button id="mhtp-download-conversation" class="mhtp-download-button" style="display:none" disabled>
                        Descargar conversación
                    </button>
                </div>
            <?php else : ?>
                <div class="mhtp-expert-avatar">
                    <img src="<?php echo esc_url(MHTP_CHAT_PLUGIN_URL . 'assets/default-avatar.jpg'); ?>" alt="Experto">
                </div>
                <div class="mhtp-expert-info">
                    <h3 class="mhtp-expert-name">Experto</h3>
                    <div class="mhtp-expert-specialty">Terapia General</div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="mhtp-chat-main">
            <?php
            $cfg      = get_option('mhtp_typebot_options');
            $url      = !empty($cfg['chatbot_url']) ? $cfg['chatbot_url'] : 'https://typebot.io/especialista-5gzhab4';
            $selected = isset($cfg['selected_params']) && is_array($cfg['selected_params']) ? $cfg['selected_params'] : array();
            $values   = isset($cfg['param_values']) && is_array($cfg['param_values']) ? $cfg['param_values'] : array();

            $available = array(
                'ExpertId'       => $expert_id,
                'ExpertName'     => !empty($expert['name']) ? $expert['name'] : '',
                'Topic'          => isset($_GET['topic']) ? sanitize_text_field($_GET['topic']) : '',
                'HistoryEnabled' => '1',
                'IsClient'       => isset($_GET['is_client']) ? sanitize_text_field($_GET['is_client']) : '',
            );

            if ( ! function_exists( 'mhtp_replace_placeholders' ) ) {
                function mhtp_replace_placeholders( $value, $vars ) {
                    return preg_replace_callback( '/{([^}]+)}/', function( $m ) use ( $vars ) {
                        return isset( $vars[ $m[1] ] ) ? $vars[ $m[1] ] : $m[0];
                    }, $value );
                }
            }

            $params = array();
            foreach ($selected as $key) {
                $param_value = '';
                if ( isset($values[$key]) && $values[$key] !== '' ) {
                    $param_value = mhtp_replace_placeholders( $values[$key], $available );
                } elseif ( isset($available[$key]) && '' !== $available[$key] ) {
                    $param_value = $available[$key];
                }
                if ( '' !== $param_value ) {
                    $params[$key] = $param_value;
                }
            }

            $slug = preg_replace('#^https?://[^/]+/#', '', untrailingslashit($url));

            ?>
            <script>
            window.typebotQueue = window.typebotQueue || [];
            function Typebot(){ typebotQueue.push(arguments); }
            Typebot('init', {
                typebot: '<?php echo esc_js( $slug ); ?>',
                <?php if ( ! empty( $params ) ) : ?>
                prefilledVariables: <?php echo wp_json_encode( $params ); ?>
                <?php endif; ?>
            });
            </script>
            <script src="https://unpkg.com/@typebot.io/js@1.0"></script>
            <?php
            ?>
            <div id="mhtp-session-overlay" class="mhtp-session-overlay" style="display:none;">
                Tu sesión ha concluido
            </div>
            <div class="mhtp-chat-footer">
                <div class="mhtp-chat-controls">
                    <button id="mhtp-end-session" class="mhtp-end-session-button">Finalizar sesión</button>
                    <div id="mhtp-session-timer" class="mhtp-session-timer">45:00</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Session End Confirmation Modal -->
<div id="mhtp-end-session-modal" class="mhtp-modal">
    <div class="mhtp-modal-content">
        <div class="mhtp-modal-header">
            <h3>Confirmar finalización</h3>
        </div>
        <div class="mhtp-modal-body">
            <p>¿Estás seguro de que deseas finalizar la sesión de chat?</p>
            <p>Una vez finalizada, no podrás continuar con esta conversación.</p>
        </div>
        <div class="mhtp-modal-footer">
            <button id="mhtp-cancel-end-session" class="mhtp-button mhtp-button-secondary">Cancelar</button>
            <button id="mhtp-confirm-end-session" class="mhtp-button mhtp-button-primary">Finalizar sesión</button>
        </div>
    </div>
</div>

<!-- Load jsPDF for PDF generation -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
