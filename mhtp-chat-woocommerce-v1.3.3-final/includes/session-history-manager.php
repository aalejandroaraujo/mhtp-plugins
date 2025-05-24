<?php
/**
 * Session history reset and management for MHTP Chat Interface
 * 
 * This file handles resetting and updating the session history for all users.
 * 
 * @package MHTP_Chat_Interface
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class to handle session history reset and management
 */
class MHTP_Session_History_Manager {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Add hook to update session history after a session is used
        add_action('mhtp_after_session_used', array($this, 'update_session_history'), 10, 3);
        
        // Add hook for admin initialization to add reset option
        add_action('admin_init', array($this, 'maybe_reset_all_session_history'));
        
        // Add hook to admin menu to add reset option
        add_action('admin_menu', array($this, 'add_reset_menu'));
        
        // Run reset on plugin activation
        register_activation_hook(MHTP_CHAT_PLUGIN_FILE, array($this, 'reset_all_session_history'));
    }
    
    /**
     * Update session history after a session is used
     * 
     * @param int $user_id The user ID
     * @param int $expert_id The expert ID
     * @param bool $success Whether the session was successfully used
     */
    public function update_session_history($user_id, $expert_id, $success) {
        if (!$success) {
            return;
        }
        
        // Get expert information
        $expert_name = $this->get_expert_name($expert_id);
        
        // Add entry to session history
        $this->add_history_entry($user_id, $expert_id, $expert_name);
    }
    
    /**
     * Get expert name from expert ID
     * 
     * @param int $expert_id The expert ID
     * @return string The expert name
     */
    private function get_expert_name($expert_id) {
        if (empty($expert_id)) {
            return 'Experto Desconocido';
        }
        
        $product = wc_get_product($expert_id);
        if (!$product) {
            return 'Experto Desconocido';
        }
        
        return $product->get_name();
    }
    
    /**
     * Add entry to session history
     * 
     * @param int $user_id The user ID
     * @param int $expert_id The expert ID
     * @param string $expert_name The expert name
     */
    private function add_history_entry($user_id, $expert_id, $expert_name) {
        // Get existing history
        $history = get_user_meta($user_id, 'mhtp_session_history', true);
        if (!is_array($history)) {
            $history = array();
        }
        
        // Calculate session duration (random between 40-60 minutes for now)
        $duration = rand(40, 60);
        
        // Add new entry
        $history[] = array(
            'expert_id' => $expert_id,
            'expert_name' => $expert_name,
            'date' => current_time('mysql'),
            'timestamp' => time(),
            'duration' => $duration
        );
        
        // Save updated history
        update_user_meta($user_id, 'mhtp_session_history', $history);
    }
    
    /**
     * Add reset menu to admin
     */
    public function add_reset_menu() {
        add_submenu_page(
            'tools.php',
            'Reset MHTP Session History',
            'Reset MHTP Session History',
            'manage_options',
            'mhtp-reset-session-history',
            array($this, 'render_reset_page')
        );
    }
    
    /**
     * Render reset page
     */
    public function render_reset_page() {
        ?>
        <div class="wrap">
            <h1>Reset MHTP Session History</h1>
            <p>This will reset the session history for all users. This action cannot be undone.</p>
            <form method="post" action="">
                <?php wp_nonce_field('mhtp_reset_session_history', 'mhtp_reset_nonce'); ?>
                <input type="hidden" name="mhtp_reset_session_history" value="1">
                <?php submit_button('Reset Session History', 'primary', 'submit', true, array('onclick' => "return confirm('Are you sure you want to reset session history for all users?');")); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Maybe reset all session history
     */
    public function maybe_reset_all_session_history() {
        if (isset($_POST['mhtp_reset_session_history']) && $_POST['mhtp_reset_session_history'] == 1) {
            // Verify nonce
            if (!isset($_POST['mhtp_reset_nonce']) || !wp_verify_nonce($_POST['mhtp_reset_nonce'], 'mhtp_reset_session_history')) {
                wp_die('Security check failed');
            }
            
            // Reset session history
            $this->reset_all_session_history();
            
            // Add admin notice
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>Session history has been reset for all users.</p></div>';
            });
        }
    }
    
    /**
     * Reset all session history
     */
    public function reset_all_session_history() {
        global $wpdb;
        
        // Delete all session history meta
        $wpdb->delete($wpdb->usermeta, array('meta_key' => 'mhtp_session_history'));
        
        // Also delete any related meta
        $wpdb->delete($wpdb->usermeta, array('meta_key' => 'mhtp_wc_session_usage'));
        
        return true;
    }
}

// Initialize the class
new MHTP_Session_History_Manager();
