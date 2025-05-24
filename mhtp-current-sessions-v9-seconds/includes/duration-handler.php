<?php
/**
 * AJAX handler for updating session duration
 * 
 * This file handles the AJAX request to update session duration in user meta.
 * 
 * @package MHTP_Chat_Interface
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class to handle AJAX requests for updating session duration
 */
class MHTP_CS_Duration_Handler {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Add AJAX handlers for logged in users
        add_action('wp_ajax_mhtp_update_session_duration', array($this, 'update_session_duration'));
    }
    
    /**
     * Update session duration in user meta
     */
    public function update_session_duration() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mhtp_chat_nonce')) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
            return;
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'User not logged in'));
            return;
        }
        
        // Get parameters
        $user_id = get_current_user_id();
        $expert_id = isset($_POST['expert_id']) ? sanitize_text_field($_POST['expert_id']) : '';
        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
        
        // Get duration in minutes and seconds
        $duration_minutes = isset($_POST['duration_minutes']) ? intval($_POST['duration_minutes']) : 0;
        $duration_seconds = isset($_POST['duration_seconds']) ? intval($_POST['duration_seconds']) : 0;
        
        // If we received the old format (just duration in minutes), use that
        if (isset($_POST['duration']) && !isset($_POST['duration_minutes'])) {
            $duration_minutes = intval($_POST['duration']);
            $duration_seconds = 0;
        }
        
        // Validate duration (must be positive and reasonable)
        if ($duration_minutes < 0 || $duration_minutes > 180) { // Max 3 hours
            $duration_minutes = 1; // Default to 1 minute if invalid
        }
        
        // Validate seconds (must be between 0-59)
        if ($duration_seconds < 0 || $duration_seconds > 59) {
            $duration_seconds = 0;
        }
        
        // Get existing history
        $history = get_user_meta($user_id, 'mhtp_session_history', true);
        if (!is_array($history)) {
            $history = array();
        }
        
        // Check if we need to extract expert name from ID
        if (strpos($expert_id, 'name:') === 0) {
            $expert_name = substr($expert_id, 5); // Remove 'name:' prefix
            $expert_id = 0; // Set ID to 0 since we don't have a real ID
        } else {
            // Try to get expert name from ID
            $expert_name = $this->get_expert_name($expert_id);
        }
        
        // Find the most recent session with this expert
        $updated = false;
        
        // First try to find and update an existing session from today
        foreach ($history as $key => $session) {
            // Check if this is a session from today with the same expert
            if (isset($session['expert_id']) && 
                (string)$session['expert_id'] === (string)$expert_id && 
                date('Y-m-d', strtotime($session['date'])) === date('Y-m-d')) {
                
                // Update the duration
                $history[$key]['duration_minutes'] = $duration_minutes;
                $history[$key]['duration_seconds'] = $duration_seconds;
                $updated = true;
                break;
            }
        }
        
        // If no existing session was found, add a new one
        if (!$updated) {
            // Add new entry
            $history[] = array(
                'expert_id' => $expert_id,
                'expert_name' => $expert_name,
                'date' => current_time('mysql'),
                'timestamp' => time(),
                'duration_minutes' => $duration_minutes,
                'duration_seconds' => $duration_seconds,
                'session_id' => $session_id
            );
        }
        
        // Save updated history
        update_user_meta($user_id, 'mhtp_session_history', $history);
        
        // Return success
        wp_send_json_success(array(
            'message' => 'Session duration updated',
            'duration_minutes' => $duration_minutes,
            'duration_seconds' => $duration_seconds
        ));
    }
    
    /**
     * Get expert name from expert ID
     * 
     * @param int $expert_id The expert ID
     * @return string The expert name
     */
    private function get_expert_name($expert_id) {
        if (empty($expert_id)) {
            return 'Experto';
        }
        
        // Try to get product from WooCommerce
        if (function_exists('wc_get_product')) {
            $product = wc_get_product($expert_id);
            if ($product) {
                $name = $product->get_name();
                
                // Clean the name
                return $this->clean_expert_name($name);
            }
        }
        
        // Try to get post title directly
        $post_title = get_the_title($expert_id);
        if (!empty($post_title)) {
            return $this->clean_expert_name($post_title);
        }
        
        return 'Experto';
    }
    
    /**
     * Clean expert name to remove specialty/description.
     *
     * @param string $full_name Full expert name with specialty.
     * @return string Cleaned expert name.
     */
    private function clean_expert_name($full_name) {
        // Special case for Lucía Apoyo
        if (strpos($full_name, 'Lucía Apoyo') !== false) {
            return 'Lucía Apoyo';
        }
        
        // Handle different types of separators (dash, hyphen, en dash, em dash)
        $name_parts = preg_split('/\s*[-–—]\s*/', $full_name, 2);
        
        // If we have parts, use the first one, otherwise use the full name
        $clean_name = !empty($name_parts[0]) ? trim($name_parts[0]) : trim($full_name);
        
        // Remove any "Experto en", "Experta en", "Especialista en" prefixes
        $clean_name = preg_replace('/^(Experto|Experta|Especialista)\s+en\s+/i', '', $clean_name);
        
        return $clean_name;
    }
}

// Initialize the class
new MHTP_CS_Duration_Handler();
