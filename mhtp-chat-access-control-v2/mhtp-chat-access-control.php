<?php
/**
 * Plugin Name: MHTP Chat Access Control
 * Description: Controls access to the Chat con expertos page based on user login and available sessions
 * Version: 1.1
 * Author: Araujo Innovations
 * Text Domain: mhtp-chat-access
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin file constant
define('MHTP_CHAT_ACCESS_PLUGIN_FILE', __FILE__);

// Include required files
require_once plugin_dir_path(__FILE__) . 'includes/class-token-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-menu-visibility.php';

/**
 * Main plugin class
 */
class MHTP_Chat_Access_Control {
    
    /**
     * Token manager instance
     */
    private $token_manager;
    
    /**
     * Menu visibility controller instance
     */
    private $menu_visibility;
    
    /**
     * Initialize the plugin
     */
    public function __construct() {
        // Initialize token manager
        $this->token_manager = new MHTP_Session_Token_Manager();
        
        // Initialize menu visibility controller
        $this->menu_visibility = new MHTP_Menu_Visibility_Control();
        
        // Hook into template_redirect to check access before page loads
        add_action('template_redirect', array($this, 'check_chat_page_access'));
        
        // Register activation hook
        register_activation_hook(__FILE__, array($this, 'activate'));
        
        // Add settings page
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // Enqueue styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Set default options if they don't exist
        if (!get_option('mhtp_chat_access_options')) {
            $defaults = array(
                'chat_page_slug' => 'chat-con-expertos',
                'redirect_page_slug' => '',
                'login_url' => wp_login_url(),
                'register_url' => wp_registration_url(),
                'sessions_url' => home_url('/product/pase-1-sesion/'),
                'not_logged_in_message' => 'Para ver el chat con expertos debes ser un usuario registrado y estar logado.',
                'no_sessions_message' => 'Para ver el chat con expertos debes tener consultas en tu balance.',
                'token_expiration_hours' => 24,
                'verify_ip' => false,
            );
            update_option('mhtp_chat_access_options', $defaults);
        }
    }
    
    /**
     * Enqueue styles
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            'mhtp-chat-access-styles',
            plugin_dir_url(__FILE__) . 'css/style.css',
            array(),
            '1.1'
        );
    }
    
    /**
     * Add settings page to admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('MHTP Chat Access Control', 'mhtp-chat-access'),
            __('Chat Access Control', 'mhtp-chat-access'),
            'manage_options',
            'mhtp-chat-access',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('mhtp_chat_access', 'mhtp_chat_access_options');
        
        add_settings_section(
            'mhtp_chat_access_section',
            __('Chat Access Control Settings', 'mhtp-chat-access'),
            array($this, 'settings_section_callback'),
            'mhtp-chat-access'
        );
        
        add_settings_field(
            'chat_page_slug',
            __('Chat Page Slug', 'mhtp-chat-access'),
            array($this, 'chat_page_slug_render'),
            'mhtp-chat-access',
            'mhtp_chat_access_section'
        );
        
        add_settings_field(
            'redirect_page_slug',
            __('Redirect Page Slug (optional)', 'mhtp-chat-access'),
            array($this, 'redirect_page_slug_render'),
            'mhtp-chat-access',
            'mhtp_chat_access_section'
        );
        
        add_settings_field(
            'login_url',
            __('Login URL', 'mhtp-chat-access'),
            array($this, 'login_url_render'),
            'mhtp-chat-access',
            'mhtp_chat_access_section'
        );
        
        add_settings_field(
            'register_url',
            __('Register URL', 'mhtp-chat-access'),
            array($this, 'register_url_render'),
            'mhtp-chat-access',
            'mhtp_chat_access_section'
        );
        
        add_settings_field(
            'sessions_url',
            __('Buy Sessions URL', 'mhtp-chat-access'),
            array($this, 'sessions_url_render'),
            'mhtp-chat-access',
            'mhtp_chat_access_section'
        );
        
        add_settings_field(
            'not_logged_in_message',
            __('Not Logged In Message', 'mhtp-chat-access'),
            array($this, 'not_logged_in_message_render'),
            'mhtp-chat-access',
            'mhtp_chat_access_section'
        );
        
        add_settings_field(
            'no_sessions_message',
            __('No Sessions Message', 'mhtp-chat-access'),
            array($this, 'no_sessions_message_render'),
            'mhtp-chat-access',
            'mhtp_chat_access_section'
        );
        
        add_settings_field(
            'token_expiration_hours',
            __('Token Expiration (hours)', 'mhtp-chat-access'),
            array($this, 'token_expiration_hours_render'),
            'mhtp-chat-access',
            'mhtp_chat_access_section'
        );
        
        add_settings_field(
            'verify_ip',
            __('Verify IP Address', 'mhtp-chat-access'),
            array($this, 'verify_ip_render'),
            'mhtp-chat-access',
            'mhtp_chat_access_section'
        );
    }
    
    /**
     * Settings section callback
     */
    public function settings_section_callback() {
        echo __('Configure access control for the Chat con expertos page.', 'mhtp-chat-access');
    }
    
    /**
     * Chat page slug field
     */
    public function chat_page_slug_render() {
        $options = get_option('mhtp_chat_access_options');
        ?>
        <input type='text' name='mhtp_chat_access_options[chat_page_slug]' value='<?php echo $options['chat_page_slug']; ?>'>
        <p class="description"><?php _e('The slug of the chat page (e.g., chat-con-expertos)', 'mhtp-chat-access'); ?></p>
        <?php
    }
    
    /**
     * Redirect page slug field
     */
    public function redirect_page_slug_render() {
        $options = get_option('mhtp_chat_access_options');
        ?>
        <input type='text' name='mhtp_chat_access_options[redirect_page_slug]' value='<?php echo isset($options['redirect_page_slug']) ? $options['redirect_page_slug'] : ''; ?>'>
        <p class="description"><?php _e('Optional: The slug of a custom page to redirect to. If empty, a default message will be shown.', 'mhtp-chat-access'); ?></p>
        <?php
    }
    
    /**
     * Login URL field
     */
    public function login_url_render() {
        $options = get_option('mhtp_chat_access_options');
        ?>
        <input type='text' name='mhtp_chat_access_options[login_url]' value='<?php echo $options['login_url']; ?>' class="regular-text">
        <p class="description"><?php _e('URL for the login page', 'mhtp-chat-access'); ?></p>
        <?php
    }
    
    /**
     * Register URL field
     */
    public function register_url_render() {
        $options = get_option('mhtp_chat_access_options');
        ?>
        <input type='text' name='mhtp_chat_access_options[register_url]' value='<?php echo $options['register_url']; ?>' class="regular-text">
        <p class="description"><?php _e('URL for the registration page', 'mhtp-chat-access'); ?></p>
        <?php
    }
    
    /**
     * Sessions URL field
     */
    public function sessions_url_render() {
        $options = get_option('mhtp_chat_access_options');
        ?>
        <input type='text' name='mhtp_chat_access_options[sessions_url]' value='<?php echo $options['sessions_url']; ?>' class="regular-text">
        <p class="description"><?php _e('URL for the page to buy sessions', 'mhtp-chat-access'); ?></p>
        <?php
    }
    
    /**
     * Not logged in message field
     */
    public function not_logged_in_message_render() {
        $options = get_option('mhtp_chat_access_options');
        ?>
        <textarea name='mhtp_chat_access_options[not_logged_in_message]' rows='3' cols='50'><?php echo $options['not_logged_in_message']; ?></textarea>
        <p class="description"><?php _e('Message to show when user is not logged in', 'mhtp-chat-access'); ?></p>
        <?php
    }
    
    /**
     * No sessions message field
     */
    public function no_sessions_message_render() {
        $options = get_option('mhtp_chat_access_options');
        ?>
        <textarea name='mhtp_chat_access_options[no_sessions_message]' rows='3' cols='50'><?php echo $options['no_sessions_message']; ?></textarea>
        <p class="description"><?php _e('Message to show when user has no available sessions', 'mhtp-chat-access'); ?></p>
        <?php
    }
    
    /**
     * Token expiration hours field
     */
    public function token_expiration_hours_render() {
        $options = get_option('mhtp_chat_access_options');
        $hours = isset($options['token_expiration_hours']) ? intval($options['token_expiration_hours']) : 24;
        ?>
        <input type='number' name='mhtp_chat_access_options[token_expiration_hours]' value='<?php echo $hours; ?>' min='1' max='168'>
        <p class="description"><?php _e('Number of hours before a session token expires', 'mhtp-chat-access'); ?></p>
        <?php
    }
    
    /**
     * Verify IP field
     */
    public function verify_ip_render() {
        $options = get_option('mhtp_chat_access_options');
        $verify_ip = isset($options['verify_ip']) ? $options['verify_ip'] : false;
        ?>
        <input type='checkbox' name='mhtp_chat_access_options[verify_ip]' <?php checked($verify_ip, true); ?> value='1'>
        <p class="description"><?php _e('Check IP address when validating tokens (not recommended for mobile users)', 'mhtp-chat-access'); ?></p>
        <?php
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        ?>
        <form action='options.php' method='post'>
            <h1><?php _e('MHTP Chat Access Control Settings', 'mhtp-chat-access'); ?></h1>
            
            <?php
            settings_fields('mhtp_chat_access');
            do_settings_sections('mhtp-chat-access');
            submit_button();
            ?>
            
        </form>
        <?php
    }
    
    /**
     * Check if user has access to chat page
     */
    public function check_chat_page_access() {
        global $wp;
        $current_slug = add_query_arg(array(), $wp->request);
        $options = get_option('mhtp_chat_access_options');
        
        // Check if we're on the chat page
        if ($current_slug === $options['chat_page_slug']) {
            // Check if user is logged in
            if (!is_user_logged_in()) {
                $this->handle_unauthorized_access('not_logged_in');
                exit;
            }
            
            // Check if user has available sessions
            $user_id = get_current_user_id();
            $available_sessions = $this->get_user_available_sessions($user_id);
            
            if ($available_sessions <= 0) {
                $this->handle_unauthorized_access('no_sessions');
                exit;
            }
        }
    }
    
    /**
     * Get number of available sessions for a user
     */
    public function get_user_available_sessions($user_id) {
        // Get available sessions from user meta
        $available_sessions = get_user_meta($user_id, 'mhtp_available_sessions', true);
        
        // If not set, default to 0
        if ($available_sessions === '' || $available_sessions === false) {
            $available_sessions = 0;
        }
        
        // Allow filtering of available sessions
        return apply_filters('mhtp_user_available_sessions', intval($available_sessions), $user_id);
    }
    
    /**
     * Handle unauthorized access
     */
    public function handle_unauthorized_access($reason) {
        $options = get_option('mhtp_chat_access_options');
        
        // If a redirect page is specified, redirect to it
        if (!empty($options['redirect_page_slug'])) {
            wp_redirect(home_url('/' . $options['redirect_page_slug'] . '/?reason=' . $reason));
            exit;
        }
        
        // Otherwise, show a custom message
        $this->display_unauthorized_message($reason);
        exit;
    }
    
    /**
     * Display unauthorized message
     */
    public function display_unauthorized_message($reason) {
        $options = get_option('mhtp_chat_access_options');
        
        // Get appropriate message and buttons based on reason
        if ($reason === 'not_logged_in') {
            $message = $options['not_logged_in_message'];
            $buttons = array(
                array(
                    'url' => $options['login_url'],
                    'text' => __('Iniciar sesión', 'mhtp-chat-access')
                ),
                array(
                    'url' => $options['register_url'],
                    'text' => __('Registrarse', 'mhtp-chat-access')
                )
            );
        } else { // no_sessions
            $message = $options['no_sessions_message'];
            $buttons = array(
                array(
                    'url' => $options['sessions_url'],
                    'text' => __('Adquirir sesiones', 'mhtp-chat-access')
                )
            );
        }
        
        // Display the message and buttons
        get_header();
        ?>
        <div class="mhtp-unauthorized-container">
            <div class="mhtp-unauthorized-message">
                <h1><?php _e('Acceso restringido', 'mhtp-chat-access'); ?></h1>
                <p><?php echo esc_html($message); ?></p>
                
                <div class="mhtp-unauthorized-buttons">
                    <?php foreach ($buttons as $button) : ?>
                        <a href="<?php echo esc_url($button['url']); ?>" class="mhtp-button"><?php echo esc_html($button['text']); ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
        get_footer();
    }
}

// Initialize the plugin
$mhtp_chat_access_control = new MHTP_Chat_Access_Control();
