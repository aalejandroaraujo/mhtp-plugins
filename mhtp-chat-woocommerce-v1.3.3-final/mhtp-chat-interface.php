<?php
/**
 * Plugin Name: MHTP Chat Interface
 * Plugin URI: https://mhtp.com
 * Description: Chat interface for Mental Health Triage Platform
 * Version: 2.0.1
 * Author: MHTP Team
 * Author URI: https://mhtp.com
 * Text Domain: mhtp-chat-interface
 * Domain Path: /languages
 * 
 * @package MHTP_Chat_Interface
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MHTP_CHAT_VERSION', '2.0.1');
define('MHTP_CHAT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MHTP_CHAT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MHTP_CHAT_PLUGIN_FILE', __FILE__);
// Botpress Chat API endpoint
define('MHTP_BOTPRESS_CHAT_API', 'https://chat.botpress.cloud/v1');

/*
 * API key for authenticating with Botpress Cloud. For security reasons you
 * should define this constant in wp-config.php and never commit your real key
 * to version control.
 */
if (!defined('MHTP_BOTPRESS_API_KEY')) {
    define('MHTP_BOTPRESS_API_KEY', '');
}

/*
 * Optional secret to validate incoming webhook requests from Botpress.
 * Set this constant in wp-config.php and configure the Botpress webhook
 * to send an Authorization header of the form "Bearer <secret>".
 */
if (!defined('MHTP_BOTPRESS_WEBHOOK_SECRET')) {
    define('MHTP_BOTPRESS_WEBHOOK_SECRET', '');
}

/**
 * Main plugin class
 */
class MHTP_Chat_Interface {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Register activation hook
        register_activation_hook(__FILE__, array($this, 'activate'));
        
        // Initialize plugin
        add_action('plugins_loaded', array($this, 'init'));
        
        // Register shortcode
        add_shortcode('mhtp_chat', array($this, 'chat_interface_shortcode'));
        
        // Register scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'register_scripts'));
        
        // Register AJAX handlers used by the front‑end script. These must be
        // available to both authenticated and guest users or chat will fail
        // to initialize.
        add_action('wp_ajax_mhtp_start_chat_session', array($this, 'ajax_start_chat_session'));
        add_action('wp_ajax_nopriv_mhtp_start_chat_session', array($this, 'ajax_start_chat_session'));

        // Register REST routes
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create necessary database tables or options
        // This is a placeholder for future functionality
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('mhtp-chat-interface', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Include required files
        $this->includes();
    }
    
    /**
     * Include required files
     */
    private function includes() {
        // Include session history manager (replaces the updater)
        require_once MHTP_CHAT_PLUGIN_DIR . 'includes/session-history-manager.php';
    }
    
    /**
     * Register scripts and styles
     */
    public function register_scripts() {
        // Register styles
        wp_register_style('mhtp-chat-interface', MHTP_CHAT_PLUGIN_URL . 'css/chat-interface.css', array(), MHTP_CHAT_VERSION);
        
        // Register aggressive button fix styles
        wp_register_style('mhtp-button-fix', MHTP_CHAT_PLUGIN_URL . 'css/button-fix-aggressive.css', array('mhtp-chat-interface'), MHTP_CHAT_VERSION);
        
        // Register scripts
        wp_register_script('mhtp-chat-interface', MHTP_CHAT_PLUGIN_URL . 'js/chat-interface.js', array('jquery'), MHTP_CHAT_VERSION, true);
        
        // Register button fix script
        wp_register_script('mhtp-button-fix', MHTP_CHAT_PLUGIN_URL . 'js/button-fix.js', array('jquery'), MHTP_CHAT_VERSION, true);
        
        // Localize script
        wp_localize_script('mhtp-chat-interface', 'mhtpChat', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mhtp_chat_nonce'),
            'i18n' => array(
                'error_no_sessions' => __('No tienes sesiones disponibles. Por favor, adquiere sesiones para continuar.', 'mhtp-chat-interface'),
                'error_session_failed' => __('Error al iniciar la sesión. Por favor, inténtalo de nuevo.', 'mhtp-chat-interface'),
                'session_started' => __('Sesión iniciada correctamente.', 'mhtp-chat-interface'),
                'session_ended' => __('La sesión ha finalizado.', 'mhtp-chat-interface'),
            )
        ));

        // Expose REST endpoint for chat messages
        wp_localize_script(
            'mhtp-chat-interface',
            'mhtpChatConfig',
            array(
                'rest_url' => rest_url('mhtp-chat/v1/message'),
                'nonce'    => wp_create_nonce('wp_rest')
            )
        );
    }
    
    /**
     * Chat interface shortcode
     */
    public function chat_interface_shortcode($atts) {
        // Extract attributes
        $atts = shortcode_atts(array(
            'expert_id' => 0,
        ), $atts, 'mhtp_chat');
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<div class="mhtp-login-required">' . 
                __('Debes iniciar sesión para acceder al chat.', 'mhtp-chat-interface') . 
                '</div>';
        }
        
        // Enqueue styles and scripts
        wp_enqueue_style('mhtp-chat-interface');
        wp_enqueue_style('mhtp-button-fix');
        wp_enqueue_script('mhtp-chat-interface');
        wp_enqueue_script('mhtp-button-fix');
        
        // Start output buffering
        ob_start();
        
        // Get expert ID from attributes or query string
        $expert_id = isset($_GET['expert_id']) ? intval($_GET['expert_id']) : intval($atts['expert_id']);
        
        if ($expert_id > 0) {
            // Get expert details
            $expert = $this->get_expert_by_id($expert_id);
            
            if ($expert) {
                // Show chat interface
                include MHTP_CHAT_PLUGIN_DIR . 'templates/chat-interface.php';
            } else {
                // Expert not found
                echo '<div class="mhtp-error">' . 
                    __('Experto no encontrado. Por favor, selecciona otro experto.', 'mhtp-chat-interface') . 
                    '</div>';
                
                // Show expert selection
                $experts = $this->get_experts();
                include MHTP_CHAT_PLUGIN_DIR . 'templates/expert-selection.php';
            }
        } else {
            // No expert_id provided, show selection screen
            $experts = $this->get_experts();
            include MHTP_CHAT_PLUGIN_DIR . 'templates/expert-selection.php';
        }
        
        // Return buffered content
        return ob_get_clean();
    }
    
    /**
     * Get expert by ID
     */
    private function get_expert_by_id($expert_id) {
        // Get product
        $product = wc_get_product($expert_id);
        
        if (!$product) {
            return false;
        }
        
        // Check if product is in the Expertos category
        $terms = get_the_terms($expert_id, 'product_cat');
        $is_expert = false;
        
        if ($terms && !is_wp_error($terms)) {
            foreach ($terms as $term) {
                if ($term->slug === 'expertos') {
                    $is_expert = true;
                    break;
                }
            }
        }
        
        if (!$is_expert) {
            return false;
        }
        
        // Get expert specialty
        $specialty = '';
        $specialty_terms = get_the_terms($expert_id, 'pa_especialidad');
        
        if ($specialty_terms && !is_wp_error($specialty_terms)) {
            $specialty = $specialty_terms[0]->name;
        }
        
        // Return expert data
        return array(
            'id' => $expert_id,
            'name' => $product->get_name(),
            'avatar' => wp_get_attachment_url($product->get_image_id()),
            'specialty' => $specialty,
            'description' => $product->get_short_description(),
        );
    }
    
    /**
     * Get all experts
     */
    private function get_experts() {
        $experts = array();
        
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
                'avatar' => wp_get_attachment_url($product->get_image_id()),
                'specialty' => $specialty,
                'description' => $product->get_short_description(),
            );
        }
        
        return $experts;
    }
    
    /**
     * AJAX handler for starting a chat session. This is triggered by the
     * JavaScript front end via admin-ajax.php and must always return a JSON
     * payload so the browser can react accordingly.
     */
    public function ajax_start_chat_session() {
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
        
        // Get user ID
        $user_id = get_current_user_id();
        
        // Get expert ID
        $expert_id = isset($_POST['expert_id']) ? intval($_POST['expert_id']) : 0;
        
        // Deduct session
        $result = $this->deduct_user_session($user_id, $expert_id);
        
        if (!$result) {
            wp_send_json_error(array('message' => 'No available sessions'));
            return;
        }

        // Prepare Botpress user and conversation
        $bp_user_id = $this->create_botpress_user($user_id);
        if (is_wp_error($bp_user_id)) {
            $error_msg = $bp_user_id->get_error_message();
            error_log('Failed to prepare chat user: ' . $error_msg);
            wp_send_json_error(array('message' => 'Failed to prepare chat user: ' . $error_msg));
            return;
        }

        $conversation_id = $this->create_botpress_conversation($bp_user_id);
        if (is_wp_error($conversation_id)) {
            $error_msg = $conversation_id->get_error_message();
            error_log('Failed to start conversation: ' . $error_msg);
            wp_send_json_error(array('message' => 'Failed to start conversation: ' . $error_msg));
            return;
        }

        // Persist conversation for future messages
        update_user_meta($user_id, 'mhtp_bp_conversation_id', $conversation_id);
        update_user_meta($user_id, 'mhtp_bp_user_id', $bp_user_id);

        wp_send_json_success(array('message' => 'Session started successfully'));
    }
    
    /**
     * Deduct a session from the user
     */
    private function deduct_user_session($user_id, $expert_id = 0) {
        // Check if MHTP Test Sessions plugin is active
        if (function_exists('mhtp_test_sessions')) {
            // Get database instance from MHTP Test Sessions
            $db = MHTP_TS_DB::get_instance();
            
            // Check if user has available sessions
            $available_sessions = $db->count_user_available_sessions($user_id);
            if ($available_sessions <= 0) {
                // Try WooCommerce sessions as fallback
                return $this->deduct_woocommerce_session($user_id, $expert_id);
            }
            
            // Use session with the correct method name
            $success = $db->use_session($user_id, $expert_id);
            
            // Trigger action for session history update
            do_action('mhtp_after_session_used', $user_id, $expert_id, $success);
            
            return $success;
        } else {
            // Fallback to WooCommerce sessions
            return $this->deduct_woocommerce_session($user_id, $expert_id);
        }
    }
    
    /**
     * Deduct a WooCommerce session
     */
    private function deduct_woocommerce_session($user_id, $expert_id = 0) {
        // Get current WooCommerce sessions
        $wc_sessions = intval(get_user_meta($user_id, 'mhtp_available_sessions', true));
        
        if ($wc_sessions > 0) {
            // User has WooCommerce sessions, decrement one
            update_user_meta($user_id, 'mhtp_available_sessions', $wc_sessions - 1);
            
            // Log the usage for tracking
            $this->log_wc_session_usage($user_id, $expert_id);
            
            // Trigger action for session history update
            do_action('mhtp_after_session_used', $user_id, $expert_id, true);
            
            return true;
        }
        
        return false;
    }

    /**
     * Create or reuse a Botpress user for the given WordPress user.
     *
     * @param int $wp_user_id WordPress user ID.
     * @return string|WP_Error Botpress user ID on success or WP_Error on failure.
     */
    private function create_botpress_user($wp_user_id) {
        if (empty(MHTP_BOTPRESS_API_KEY)) {
            return new WP_Error('bp_no_key', 'Botpress API key not configured');
        }

        $bp_user_id = 'wp-' . $wp_user_id;

        $url          = trailingslashit(MHTP_BOTPRESS_CHAT_API) . 'users';
        $request_args = array(
            'headers' => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . MHTP_BOTPRESS_API_KEY,
            ),
            'body'    => wp_json_encode(array('id' => $bp_user_id)),
            'timeout' => 15,
        );
        $response = wp_remote_post($url, $request_args);

        if (is_wp_error($response)) {
            error_log('Botpress user creation request: ' . $url . ' ' . print_r($request_args, true));
            error_log('Botpress user creation failed: ' . $response->get_error_message());
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        if ($code !== 201 && $code !== 200 && $code !== 409) {
            error_log('Botpress user creation request: ' . $url . ' ' . print_r($request_args, true));
            error_log('Botpress user creation response (' . $code . '): ' . $body);
            return new WP_Error('bp_user_failed', 'Unexpected status from Botpress: ' . $code);
        }

        return $bp_user_id;
    }

    /**
     * Create a conversation for the Botpress user.
     *
     * @param string $bp_user_id Botpress user ID.
     * @return string|WP_Error Conversation ID or error.
     */
    private function create_botpress_conversation($bp_user_id) {
        if (empty(MHTP_BOTPRESS_API_KEY)) {
            return new WP_Error('bp_no_key', 'Botpress API key not configured');
        }

        $url = trailingslashit(MHTP_BOTPRESS_CHAT_API) . 'events';
        $payload = array(
            'type'  => 'text',
            'text'  => 'start',
            'userId' => $bp_user_id,
        );

        $request_args = array(
            'headers' => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . MHTP_BOTPRESS_API_KEY,
            ),
            'body'    => wp_json_encode($payload),
            'timeout' => 15,
        );

        $response = wp_remote_post($url, $request_args);

        if (is_wp_error($response)) {
            error_log('Botpress conversation creation request: ' . $url . ' ' . print_r($request_args, true));
            error_log('Botpress conversation creation failed: ' . $response->get_error_message());
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        if ($code < 200 || $code >= 300) {
            error_log('Botpress conversation creation request: ' . $url . ' ' . print_r($request_args, true));
            error_log('Botpress conversation creation response (' . $code . '): ' . $body);
            return new WP_Error('bp_conversation_failed', 'Unexpected status from Botpress: ' . $code);
        }

        $decoded = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Invalid JSON from Botpress when creating conversation: ' . json_last_error_msg());
            return new WP_Error('bp_conversation_failed', 'Invalid JSON response');
        }

        if (empty($decoded['conversationId'])) {
            error_log('Botpress response missing conversationId: ' . $body);
            return new WP_Error('bp_conversation_failed', 'No conversationId returned');
        }

        return sanitize_text_field($decoded['conversationId']);
    }

    /**
     * Handle webhook callbacks from Botpress (optional).
     *
     * @param WP_REST_Request $request Incoming request.
     * @return WP_REST_Response
     */
    public function rest_webhook_handler(WP_REST_Request $request) {
        $auth_header = $request->get_header('Authorization');
        $expected    = 'Bearer ' . MHTP_BOTPRESS_WEBHOOK_SECRET;
        if ($auth_header !== $expected) {
            error_log('Webhook authorization failed: ' . $auth_header);
            return new WP_REST_Response(null, 401);
        }

        $payload = $request->get_json_params();
        error_log('Botpress webhook received: ' . wp_json_encode($payload));

        // Attempt to extract the conversation ID and bot reply text
        $conversation_id = '';
        if (isset($payload['conversationId'])) {
            $conversation_id = sanitize_text_field($payload['conversationId']);
        }

        $bot_reply = '';
        if (isset($payload['messages'][0]['payload']['text'])) {
            $bot_reply = sanitize_text_field($payload['messages'][0]['payload']['text']);
        } elseif (isset($payload['payload']['text'])) {
            $bot_reply = sanitize_text_field($payload['payload']['text']);
        }

        if ($conversation_id && $bot_reply) {
            // Try to locate the WP user by Botpress conversation ID
            $users = get_users(array(
                'meta_key'   => 'mhtp_bp_conversation_id',
                'meta_value' => $conversation_id,
                'fields'     => 'ID',
                'number'     => 1,
            ));

            if (!empty($users)) {
                $user_id = (int) $users[0];
                update_user_meta($user_id, 'mhtp_last_bot_reply', $bot_reply);
            }
        }

        return new WP_REST_Response(array('received' => true));
    }

    /**
     * Register REST routes.
     */
    public function register_rest_routes() {
        error_log('MHTP Chat Interface → register_rest_routes() invoked');
        register_rest_route(
            'mhtp-chat/v1',
            '/message',
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'rest_proxy_message'),
                'permission_callback' => function() {
                    error_log('Permission callback invoked by user ' . get_current_user_id());
                    return true;
                },
                'args'                => array(
                    'message' => array(
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
            )
        );

        // Optional endpoint to receive Botpress webhook calls
        register_rest_route(
            'mhtp-chat/v1',
            '/webhook',
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'rest_webhook_handler'),
                'permission_callback' => '__return_true',
            )
        );
    }

    /**
     * Forward user message to Botpress and return the reply.
     *
     * @param WP_REST_Request $request Request object.
     *
     * @return WP_REST_Response
     */
    public function rest_proxy_message(WP_REST_Request $request) {
        error_log('→ rest_proxy_message payload=' . print_r($request->get_params(), true));
        $message = $request->get_param('message');

        $bp_user_id      = get_user_meta(get_current_user_id(), 'mhtp_bp_user_id', true);
        $conversation_id = get_user_meta(get_current_user_id(), 'mhtp_bp_conversation_id', true);

        if (!$bp_user_id) {
            error_log('Botpress user not initialized');
            return new WP_REST_Response(array('error' => 'User not initialized'), 400);
        }

        if (empty(MHTP_BOTPRESS_API_KEY)) {
            error_log('Botpress API key missing');
            return new WP_REST_Response(array('error' => 'Botpress not configured'), 500);
        }

        $botpress_url = trailingslashit(MHTP_BOTPRESS_CHAT_API) . 'events';

        $payload = array(
            'type'   => 'text',
            'text'   => $message,
            'userId' => $bp_user_id,
        );
        if (!empty($conversation_id)) {
            $payload['conversationId'] = $conversation_id;
        }

        $response = wp_remote_post(
            $botpress_url,
            array(
                'headers' => array(
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . MHTP_BOTPRESS_API_KEY,
                ),
                'body'    => wp_json_encode($payload),
                'timeout' => 15,
            )
        );

        if (is_wp_error($response)) {
            error_log('Botpress request failed: ' . $response->get_error_message());
            return new WP_REST_Response(array('error' => 'Failed to contact Botpress'), 500);
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($code < 200 || $code >= 300) {
            error_log('Unexpected Botpress status ' . $code . ': ' . $body);
            return new WP_REST_Response(array('error' => 'Unexpected response from Botpress'), 502);
        }

        if (empty($body)) {
            error_log('Botpress returned empty body');
            return new WP_REST_Response(array('error' => 'Empty response from Botpress'), 502);
        }

        error_log('Botpress response: ' . $body);

        $decoded = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Invalid JSON from Botpress: ' . json_last_error_msg());
            return new WP_REST_Response(array('error' => 'Invalid response from Botpress'), 502);
        }

        if (empty($conversation_id) && !empty($decoded['conversationId'])) {
            update_user_meta(get_current_user_id(), 'mhtp_bp_conversation_id', sanitize_text_field($decoded['conversationId']));
            $conversation_id = $decoded['conversationId'];
        }

        // Extract first bot message if available
        $reply = '';
        if (isset($decoded['payload']['text'])) {
            $reply = $decoded['payload']['text'];
        } elseif (isset($decoded['messages'][0]['payload']['text'])) {
            $reply = $decoded['messages'][0]['payload']['text'];
        }

        return new WP_REST_Response(array('text' => $reply), $code);
    }
    
    /**
     * Log WooCommerce session usage
     */
    private function log_wc_session_usage($user_id, $expert_id) {
        // Create a record of this usage for reporting
        $usage = array(
            'user_id' => $user_id,
            'expert_id' => $expert_id,
            'timestamp' => current_time('mysql'),
            'type' => 'woocommerce'
        );
        
        // Store in a custom user meta for tracking
        $usage_history = get_user_meta($user_id, 'mhtp_wc_session_usage', true);
        if (!is_array($usage_history)) {
            $usage_history = array();
        }
        
        $usage_history[] = $usage;
        update_user_meta($user_id, 'mhtp_wc_session_usage', $usage_history);
    }
}

// Initialize the plugin
$mhtp_chat_interface = new MHTP_Chat_Interface();
