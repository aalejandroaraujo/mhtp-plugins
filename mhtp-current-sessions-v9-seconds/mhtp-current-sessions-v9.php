<?php
/**
 * Plugin Name: MHTP Current Sessions V9.1.1
 * Plugin URI: https://araujo-innovations.com/plugins/mhtp-current-sessions-v9
 * Description: Plugin para mostrar las sesiones actuales del usuario con estilos limitados al shortcode
 * Version: 9.1.1
 * Author: Araujo Innovations
 * Author URI: https://araujo-innovations.com
 * Text Domain: mhtp-current-sessions-v9
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('MHTP_CS_VERSION', '9.1.1');
define('MHTP_CS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MHTP_CS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once MHTP_CS_PLUGIN_DIR . 'includes/session-history-manager.php';
require_once MHTP_CS_PLUGIN_DIR . 'includes/duration-handler.php';

/**
 * Main class for the current sessions display.
 */
class MHTP_Current_Sessions_V9 {
    /**
     * Session history manager instance
     */
    private $session_history_manager;
    
    /**
     * Initialize the class.
     */
    public function __construct() {
        // Initialize session history manager
        $this->session_history_manager = new MHTP_CS_Session_History_Manager();
        
        // Register shortcode
        add_shortcode('mhtp_current_sessions', array($this, 'render_current_sessions'));
        
        // Add necessary styles and scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Enqueue scripts and styles.
     */
    public function enqueue_scripts() {
        // Add timestamp to force cache refresh
        $timestamp = time();
        
        // Enqueue CSS
        wp_enqueue_style(
            'mhtp-current-sessions-css',
            MHTP_CS_PLUGIN_URL . 'css/current-sessions.css',
            array(),
            MHTP_CS_VERSION . '.' . $timestamp
        );
        
        // Enqueue JavaScript
        wp_enqueue_script(
            'mhtp-current-sessions-js',
            MHTP_CS_PLUGIN_URL . 'js/current-sessions.js',
            array('jquery'),
            MHTP_CS_VERSION . '.' . $timestamp,
            true
        );
        
        // Enqueue session duration tracker script
        wp_enqueue_script(
            'mhtp-session-duration-tracker',
            MHTP_CS_PLUGIN_URL . 'js/session-duration-tracker.js',
            array('jquery'),
            MHTP_CS_VERSION . '.' . $timestamp,
            true
        );
        
        // Pass variables to JavaScript
        wp_localize_script(
            'mhtp-current-sessions-js',
            'mhtp_cs_vars',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mhtp_cs_nonce'),
                'i18n' => array(
                    'no_sessions' => __('No tienes sesiones disponibles', 'mhtp-current-sessions-v9'),
                    'find_expert' => __('Buscar experto', 'mhtp-current-sessions-v9'),
                    'view_summary' => __('Ver resumen', 'mhtp-current-sessions-v9'),
                )
            )
        );
    }
    
    /**
     * Render the current sessions shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string Shortcode output.
     */
    public function render_current_sessions($atts) {
        // Parse shortcode attributes
        $atts = shortcode_atts(
            array(
                'limit' => -1,
                'show_filter' => 'no',
                'experts_page' => '',
            ),
            $atts,
            'mhtp_current_sessions'
        );
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<div class="mhtp-cs-not-logged-in">' . 
                __('Debes iniciar sesión para ver tus sesiones', 'mhtp-current-sessions-v9') . 
                '</div>';
        }
        
        // Get current user
        $user_id = get_current_user_id();
        
        // Get available sessions count from WooCommerce orders
        $available_sessions = $this->get_available_sessions_count($user_id);
        
        // Get session history using the session history manager
        $history_sessions = $this->session_history_manager->get_session_history($user_id);
        
        // Start output buffer
        ob_start();
        
        // Include template
        include MHTP_CS_PLUGIN_DIR . 'templates/current-sessions-template.php';
        
        // Return the buffered content
        return ob_get_clean();
    }
    
    /**
     * Get available sessions count for a user from WooCommerce orders.
     *
     * @param int $user_id User ID.
     * @return int Number of available sessions.
     */
    private function get_available_sessions_count($user_id) {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return 0;
        }
        
        $available_sessions = 0;
        
        // Define the specific product IDs for session packages
        $session_products = array(
            487 => 10, // "10 Sesiones" product gives 10 sessions
            486 => 5,  // "5 Sesiones" product gives 5 sessions
            483 => 1   // "1 Sesion" product gives 1 session
        );
        
        // Get completed orders for the user
        $args = array(
            'customer_id' => $user_id,
            'status' => array('wc-completed'),
            'limit' => -1,
        );
        
        $orders = wc_get_orders($args);
        
        // Loop through orders to find session products by ID
        foreach ($orders as $order) {
            $items = $order->get_items();
            
            foreach ($items as $item) {
                $product_id = $item->get_product_id();
                
                // Check if this product is one of our session products
                if (isset($session_products[$product_id])) {
                    // Add the appropriate number of sessions based on product ID and quantity
                    $available_sessions += $session_products[$product_id] * $item->get_quantity();
                }
            }
        }
        
        // Check for legacy sessions in user meta as a fallback
        $legacy_sessions = intval(get_user_meta($user_id, 'mhtp_available_sessions', true));
        if ($legacy_sessions > 0) {
            $available_sessions += $legacy_sessions;
        }
        
        // Subtract used sessions (if you have a way to track them)
        // For now, we'll just return the total purchased sessions
        
        return $available_sessions;
    }
}

// Initialize the plugin
function mhtp_current_sessions_v9_init() {
    new MHTP_Current_Sessions_V9();
}
add_action('plugins_loaded', 'mhtp_current_sessions_v9_init');

// Create templates directory if it doesn't exist
function mhtp_current_sessions_v9_activate() {
    $templates_dir = MHTP_CS_PLUGIN_DIR . 'templates';
    if (!file_exists($templates_dir)) {
        mkdir($templates_dir, 0755, true);
    }
    
    $includes_dir = MHTP_CS_PLUGIN_DIR . 'includes';
    if (!file_exists($includes_dir)) {
        mkdir($includes_dir, 0755, true);
    }
}
register_activation_hook(__FILE__, 'mhtp_current_sessions_v9_activate');
