<?php
/**
 * Token management functionality for MHTP Chat Access Control
 * 
 * This file contains functions for generating, validating, and managing
 * security tokens for chat session access.
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Token Management Class
 */
class MHTP_Session_Token_Manager {
    
    /**
     * Initialize the token manager
     */
    public function __construct() {
        // Create token table on activation
        register_activation_hook(MHTP_CHAT_ACCESS_PLUGIN_FILE, array($this, 'create_token_table'));
        
        // Add token cleanup to scheduled events
        add_action('mhtp_cleanup_expired_tokens', array($this, 'cleanup_expired_tokens'));
        
        // Add AJAX handlers for token operations
        add_action('wp_ajax_mhtp_generate_session_token', array($this, 'ajax_generate_token'));
        add_action('wp_ajax_mhtp_validate_session_token', array($this, 'ajax_validate_token'));
    }
    
    /**
     * Create token database table
     */
    public function create_token_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mhtp_session_tokens';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            token_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            token VARCHAR(255) NOT NULL,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            session_id BIGINT(20) UNSIGNED NOT NULL,
            created_timestamp DATETIME NOT NULL,
            expiration_timestamp DATETIME NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            ip_address VARCHAR(100),
            last_activity DATETIME,
            PRIMARY KEY (token_id),
            UNIQUE KEY token (token),
            KEY user_id (user_id),
            KEY session_id (session_id),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Schedule token cleanup event if not already scheduled
        if (!wp_next_scheduled('mhtp_cleanup_expired_tokens')) {
            wp_schedule_event(time(), 'daily', 'mhtp_cleanup_expired_tokens');
        }
    }
    
    /**
     * Generate a new session token
     */
    public function generate_token($user_id, $session_id = 0) {
        global $wpdb;
        
        // Generate a random token
        $token = wp_generate_password(32, false);
        
        // Get token expiration time from settings (default: 24 hours)
        $options = get_option('mhtp_chat_access_options');
        $expiration_hours = isset($options['token_expiration_hours']) ? intval($options['token_expiration_hours']) : 24;
        
        // Calculate expiration timestamp
        $created = current_time('mysql');
        $expiration = date('Y-m-d H:i:s', strtotime("+{$expiration_hours} hours", strtotime($created)));
        
        // Get user IP address
        $ip_address = $this->get_user_ip();
        
        // Insert token into database
        $table_name = $wpdb->prefix . 'mhtp_session_tokens';
        $wpdb->insert(
            $table_name,
            array(
                'token' => $token,
                'user_id' => $user_id,
                'session_id' => $session_id,
                'created_timestamp' => $created,
                'expiration_timestamp' => $expiration,
                'status' => 'active',
                'ip_address' => $ip_address,
                'last_activity' => $created
            ),
            array('%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($wpdb->insert_id) {
            return $token;
        }
        
        return false;
    }
    
    /**
     * Validate a session token
     */
    public function validate_token($token) {
        global $wpdb;
        
        if (empty($token)) {
            return false;
        }
        
        $table_name = $wpdb->prefix . 'mhtp_session_tokens';
        
        // Get token from database
        $token_data = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE token = %s AND status = 'active'",
                $token
            )
        );
        
        // If token doesn't exist or is not active
        if (!$token_data) {
            return false;
        }
        
        // Check if token has expired
        $current_time = current_time('mysql');
        if ($token_data->expiration_timestamp < $current_time) {
            // Update token status to expired
            $wpdb->update(
                $table_name,
                array('status' => 'expired'),
                array('token_id' => $token_data->token_id),
                array('%s'),
                array('%d')
            );
            return false;
        }
        
        // Optional: Check if IP address matches (can be disabled for mobile users with changing IPs)
        $options = get_option('mhtp_chat_access_options');
        if (isset($options['verify_ip']) && $options['verify_ip']) {
            $current_ip = $this->get_user_ip();
            if ($token_data->ip_address !== $current_ip) {
                // Log suspicious activity but don't invalidate token
                // This is just a warning as mobile IPs can change frequently
                do_action('mhtp_suspicious_token_access', $token_data, $current_ip);
            }
        }
        
        // Update last activity
        $wpdb->update(
            $table_name,
            array('last_activity' => $current_time),
            array('token_id' => $token_data->token_id),
            array('%s'),
            array('%d')
        );
        
        // Return user ID and session ID
        return array(
            'user_id' => $token_data->user_id,
            'session_id' => $token_data->session_id,
            'created' => $token_data->created_timestamp,
            'expires' => $token_data->expiration_timestamp
        );
    }
    
    /**
     * Revoke a token
     */
    public function revoke_token($token) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mhtp_session_tokens';
        
        return $wpdb->update(
            $table_name,
            array('status' => 'revoked'),
            array('token' => $token),
            array('%s'),
            array('%s')
        );
    }
    
    /**
     * Cleanup expired tokens
     */
    public function cleanup_expired_tokens() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mhtp_session_tokens';
        $current_time = current_time('mysql');
        
        // Update status of expired tokens
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE $table_name SET status = 'expired' WHERE status = 'active' AND expiration_timestamp < %s",
                $current_time
            )
        );
        
        // Optionally delete very old tokens (older than 30 days)
        $thirty_days_ago = date('Y-m-d H:i:s', strtotime('-30 days', strtotime($current_time)));
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $table_name WHERE created_timestamp < %s",
                $thirty_days_ago
            )
        );
    }
    
    /**
     * AJAX handler for generating a token
     */
    public function ajax_generate_token() {
        // Check nonce
        check_ajax_referer('mhtp_chat_access_nonce', 'nonce');
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
        }
        
        $user_id = get_current_user_id();
        $session_id = isset($_POST['session_id']) ? intval($_POST['session_id']) : 0;
        
        // Generate token
        $token = $this->generate_token($user_id, $session_id);
        
        if ($token) {
            wp_send_json_success(array('token' => $token));
        } else {
            wp_send_json_error('Failed to generate token');
        }
    }
    
    /**
     * AJAX handler for validating a token
     */
    public function ajax_validate_token() {
        // Check nonce
        check_ajax_referer('mhtp_chat_access_nonce', 'nonce');
        
        $token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';
        
        // Validate token
        $result = $this->validate_token($token);
        
        if ($result) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error('Invalid or expired token');
        }
    }
    
    /**
     * Get user IP address
     */
    private function get_user_ip() {
        $ip = '';
        
        // Check for proxy
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return $ip;
    }
}
