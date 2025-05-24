<?php
/**
 * Session history update fix for MHTP Chat Interface
 * 
 * This file adds a hook to update the session history when a session is used.
 * 
 * @package MHTP_Chat_Interface
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class to handle session history updates
 */
class MHTP_Session_History_Updater {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Add hook to update session history after a session is used
        add_action('mhtp_after_session_used', array($this, 'update_session_history'), 10, 3);
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
        
        // Add new entry
        $history[] = array(
            'expert_id' => $expert_id,
            'expert_name' => $expert_name,
            'date' => current_time('mysql'),
            'timestamp' => time()
        );
        
        // Save updated history
        update_user_meta($user_id, 'mhtp_session_history', $history);
    }
}

// Initialize the class
new MHTP_Session_History_Updater();
