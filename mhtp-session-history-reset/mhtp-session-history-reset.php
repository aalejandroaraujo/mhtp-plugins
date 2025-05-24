<?php
/**
 * Plugin Name: MHTP Session History Reset
 * Plugin URI: https://araujo-innovations.com/plugins/mhtp-session-history-reset
 * Description: Plugin sencillo para borrar el historial de sesiones de todos los usuarios
 * Version: 1.0.0
 * Author: Araujo Innovations
 * Author URI: https://araujo-innovations.com
 * Text Domain: mhtp-session-history-reset
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Main class for the session history reset.
 */
class MHTP_Session_History_Reset {
    /**
     * Initialize the class.
     */
    public function __construct() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register admin actions
        add_action('admin_init', array($this, 'handle_reset_action'));
    }
    
    /**
     * Add admin menu item.
     */
    public function add_admin_menu() {
        add_management_page(
            'MHTP Borrar Historial de Sesiones',
            'MHTP Borrar Historial',
            'manage_options',
            'mhtp-session-history-reset',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Render admin page.
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1>MHTP Borrar Historial de Sesiones</h1>
            
            <div class="card" style="max-width: 600px; margin-top: 20px; padding: 20px; background-color: #fff; border: 1px solid #ccd0d4; border-radius: 4px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <h2 style="margin-top: 0;">Borrar Historial de Sesiones</h2>
                
                <p>Esta herramienta borrará <strong>todo</strong> el historial de sesiones de todos los usuarios. Esta acción no se puede deshacer.</p>
                
                <p>El historial de sesiones se almacena en el meta de usuario con la clave <code>mhtp_session_history</code>.</p>
                
                <form method="post" action="">
                    <?php wp_nonce_field('mhtp_reset_session_history', 'mhtp_reset_nonce'); ?>
                    <input type="hidden" name="mhtp_reset_session_history" value="1">
                    
                    <p>
                        <label>
                            <input type="checkbox" name="mhtp_confirm_reset" value="1" required>
                            Confirmo que quiero borrar todo el historial de sesiones
                        </label>
                    </p>
                    
                    <?php submit_button('Borrar Historial de Sesiones', 'primary', 'submit', true, array(
                        'onclick' => "return confirm('¿Estás seguro de que quieres borrar todo el historial de sesiones? Esta acción no se puede deshacer.');"
                    )); ?>
                </form>
            </div>
        </div>
        <?php
    }
    
    /**
     * Handle reset action.
     */
    public function handle_reset_action() {
        // Check if reset action was triggered
        if (!isset($_POST['mhtp_reset_session_history']) || $_POST['mhtp_reset_session_history'] != 1) {
            return;
        }
        
        // Verify nonce
        if (!isset($_POST['mhtp_reset_nonce']) || !wp_verify_nonce($_POST['mhtp_reset_nonce'], 'mhtp_reset_session_history')) {
            wp_die('Error de seguridad. Por favor, intenta de nuevo.');
        }
        
        // Check if user confirmed reset
        if (!isset($_POST['mhtp_confirm_reset']) || $_POST['mhtp_confirm_reset'] != 1) {
            add_settings_error(
                'mhtp_session_history_reset',
                'mhtp_reset_not_confirmed',
                'Debes confirmar que quieres borrar el historial de sesiones.',
                'error'
            );
            return;
        }
        
        // Reset session history
        $result = $this->reset_all_session_history();
        
        // Show success or error message
        if ($result) {
            add_settings_error(
                'mhtp_session_history_reset',
                'mhtp_reset_success',
                'El historial de sesiones ha sido borrado correctamente.',
                'success'
            );
        } else {
            add_settings_error(
                'mhtp_session_history_reset',
                'mhtp_reset_error',
                'Ha ocurrido un error al borrar el historial de sesiones.',
                'error'
            );
        }
    }
    
    /**
     * Reset all session history.
     * 
     * @return bool True on success, false on failure.
     */
    private function reset_all_session_history() {
        global $wpdb;
        
        // Delete all session history meta
        $result = $wpdb->delete($wpdb->usermeta, array('meta_key' => 'mhtp_session_history'));
        
        return $result !== false;
    }
}

// Initialize the plugin
function mhtp_session_history_reset_init() {
    new MHTP_Session_History_Reset();
}
add_action('plugins_loaded', 'mhtp_session_history_reset_init');
