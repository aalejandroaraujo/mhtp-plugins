<?php
/**
 * Session history management for MHTP Current Sessions
 * 
 * This file handles retrieving and displaying real session history.
 * 
 * @package MHTP_Current_Sessions_V9
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class to handle session history management
 */
class MHTP_CS_Session_History_Manager {
    
    /**
     * Get session history for a user.
     * This reads real session history from user meta instead of generating dummy data.
     *
     * @param int $user_id User ID.
     * @return array Array of session history entries.
     */
    public function get_session_history($user_id) {
        $history = array();
        
        // Get session history from user meta
        $session_history = get_user_meta($user_id, 'mhtp_session_history', true);
        
        // If we have real session history, format it for display
        if (is_array($session_history) && !empty($session_history)) {
            $counter = 1;
            foreach ($session_history as $session) {
                // Get expert name with better error handling
                $expert_name = $this->get_expert_name_safe($session['expert_id'], $session['expert_name'] ?? '');
                
                // Format the duration to show minutes and seconds
                if (isset($session['duration_minutes']) && isset($session['duration_seconds'])) {
                    // New format with minutes and seconds
                    $minutes = intval($session['duration_minutes']);
                    $seconds = intval($session['duration_seconds']);
                    $duration_text = $minutes . ':' . ($seconds < 10 ? '0' : '') . $seconds . ' minutos';
                } else if (isset($session['duration'])) {
                    // Old format with just minutes
                    $duration = intval($session['duration']);
                    $duration_text = $duration . ' minutos';
                } else {
                    // Fallback
                    $duration_text = '0 minutos';
                }
                
                // Format the session data to match the expected structure
                $history[] = array(
                    'id' => $counter++,
                    'date' => isset($session['date']) ? $session['date'] : current_time('mysql'),
                    'expert_id' => isset($session['expert_id']) ? $session['expert_id'] : 0,
                    'expert_name' => $expert_name,
                    'duration' => $duration_text,
                    'has_summary' => isset($session['has_summary']) ? $session['has_summary'] : false,
                );
            }
        }
        
        // Return the history (will be empty if no real sessions exist)
        return $history;
    }
    
    /**
     * Get expert name with better error handling.
     * This tries multiple methods to get the expert name.
     *
     * @param int $expert_id The expert ID.
     * @param string $fallback_name Fallback name if available.
     * @return string The expert name.
     */
    private function get_expert_name_safe($expert_id, $fallback_name = '') {
        // If no expert ID, try to use fallback name
        if (empty($expert_id) && !empty($fallback_name)) {
            return $this->clean_expert_name($fallback_name);
        }
        
        // Try to get product from WooCommerce
        if (!empty($expert_id) && function_exists('wc_get_product')) {
            $product = wc_get_product($expert_id);
            if ($product) {
                return $this->clean_expert_name($product->get_name());
            }
        }
        
        // Try to get post title directly
        if (!empty($expert_id)) {
            $post_title = get_the_title($expert_id);
            if (!empty($post_title)) {
                return $this->clean_expert_name($post_title);
            }
        }
        
        // If we have a fallback name, use it
        if (!empty($fallback_name)) {
            return $this->clean_expert_name($fallback_name);
        }
        
        // Last resort, check if we can find the expert in the Expertos category
        $experts = $this->get_all_experts();
        foreach ($experts as $expert) {
            if ($expert['id'] == $expert_id) {
                return $this->clean_expert_name($expert['name']);
            }
        }
        
        // If all else fails
        return 'Experto';
    }
    
    /**
     * Get all experts from WooCommerce products.
     *
     * @return array Array of experts.
     */
    private function get_all_experts() {
        $experts = array();
        
        // Check if WooCommerce is active
        if (!function_exists('wc_get_products')) {
            return $experts;
        }
        
        // Get products in Expertos category
        $args = array(
            'category' => array('expertos'),
            'limit' => -1,
            'status' => 'publish',
        );
        
        $products = wc_get_products($args);
        
        foreach ($products as $product) {
            // Get expert specialty
            $specialty = '';
            $specialty_terms = get_the_terms($product->get_id(), 'pa_especialidad');
            
            if ($specialty_terms && !is_wp_error($specialty_terms)) {
                $specialty = $specialty_terms[0]->name;
            }
            
            // Add expert to array
            $experts[] = array(
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'specialty' => $specialty,
            );
        }
        
        return $experts;
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
