<?php
/**
 * Clase para la administración del plugin
 *
 * @package MHTP_Test_Sessions
 */

// Si este archivo es llamado directamente, abortar
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para la administración del plugin
 */
class MHTP_TS_Admin {

    /**
     * Instancia única de la clase
     */
    private static $instance = null;

    /**
     * Obtener instancia única de la clase
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Registrar menús de administración
        add_action('admin_menu', array($this, 'register_admin_menus'));
        
        // Registrar scripts y estilos de administración
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Registrar acciones AJAX
        add_action('wp_ajax_mhtp_ts_add_sessions', array($this, 'ajax_add_sessions'));
        add_action('wp_ajax_mhtp_ts_delete_session', array($this, 'ajax_delete_session'));
    }

    /**
     * Registrar menús de administración
     */
    public function register_admin_menus() {
        // Menú principal
        add_menu_page(
            __('Sesiones de Prueba', 'mhtp-test-sessions'),
            __('Sesiones de Prueba', 'mhtp-test-sessions'),
            'manage_test_sessions',
            'mhtp-test-sessions',
            array($this, 'render_main_page'),
            'dashicons-tickets',
            30
        );
        
        // Submenú para gestionar sesiones
        add_submenu_page(
            'mhtp-test-sessions',
            __('Gestionar Sesiones', 'mhtp-test-sessions'),
            __('Gestionar Sesiones', 'mhtp-test-sessions'),
            'manage_test_sessions',
            'mhtp-test-sessions',
            array($this, 'render_main_page')
        );
        
        // Submenú para asignación masiva
        add_submenu_page(
            'mhtp-test-sessions',
            __('Asignación Masiva', 'mhtp-test-sessions'),
            __('Asignación Masiva', 'mhtp-test-sessions'),
            'manage_test_sessions',
            'mhtp-ts-bulk-manager',
            array(MHTP_TS_Bulk_Manager::get_instance(), 'render_bulk_manager_page')
        );
        
        // Submenú para estadísticas
        add_submenu_page(
            'mhtp-test-sessions',
            __('Estadísticas', 'mhtp-test-sessions'),
            __('Estadísticas', 'mhtp-test-sessions'),
            'view_test_sessions_stats',
            'mhtp-ts-statistics',
            array(MHTP_TS_Statistics::get_instance(), 'render_statistics_page')
        );
        
        // Submenú para configuración
        add_submenu_page(
            'mhtp-test-sessions',
            __('Configuración', 'mhtp-test-sessions'),
            __('Configuración', 'mhtp-test-sessions'),
            'manage_test_sessions',
            'mhtp-ts-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Registrar scripts y estilos de administración
     */
    public function enqueue_admin_scripts($hook) {
        // Verificar si estamos en una página del plugin
        if (strpos($hook, 'mhtp-ts') === false) {
            return;
        }
        
        // Registrar y encolar estilos
        wp_enqueue_style(
            'mhtp-ts-admin-css',
            MHTP_TS_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            MHTP_TS_VERSION
        );
        
        // Registrar y encolar scripts
        wp_enqueue_script(
            'mhtp-ts-admin-js',
            MHTP_TS_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            MHTP_TS_VERSION,
            true
        );
        
        // Pasar variables a JavaScript
        wp_localize_script(
            'mhtp-ts-admin-js',
            'mhtp_ts_admin',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mhtp_ts_admin_nonce'),
                'i18n' => array(
                    'confirm_delete' => __('¿Estás seguro de que deseas eliminar esta sesión?', 'mhtp-test-sessions'),
                    'error_message' => __('Ha ocurrido un error al procesar la solicitud. Por favor, inténtalo de nuevo.', 'mhtp-test-sessions'),
                    'processing' => __('Procesando...', 'mhtp-test-sessions'),
                    'adding_sessions' => __('Añadiendo sesiones...', 'mhtp-test-sessions'),
                    'deleting' => __('Eliminando...', 'mhtp-test-sessions'),
                    'add_sessions' => __('Añadir Sesiones', 'mhtp-test-sessions'),
                    'delete' => __('Eliminar', 'mhtp-test-sessions')
                )
            )
        );
    }

    /**
     * Renderizar página principal
     */
    public function render_main_page() {
        // Obtener usuarios
        $users_query = new WP_User_Query(array(
            'orderby' => 'display_name',
            'order' => 'ASC'
        ));
        $users = $users_query->get_results();
        
        // Obtener categorías disponibles
        $settings = get_option('mhtp_ts_settings', array());
        $categories = isset($settings['categories']) ? $settings['categories'] : array('test', 'promo', 'demo');
        
        // Incluir template
        include MHTP_TS_PLUGIN_DIR . 'admin/templates/main-page.php';
    }

    /**
     * Renderizar página de configuración
     */
    public function render_settings_page() {
        // Obtener configuración actual
        $settings = get_option('mhtp_ts_settings', array());
        
        // Valores por defecto
        $default_expiry_days = isset($settings['default_expiry_days']) ? $settings['default_expiry_days'] : 30;
        $default_category = isset($settings['default_category']) ? $settings['default_category'] : 'test';
        $categories = isset($settings['categories']) ? $settings['categories'] : array('test', 'promo', 'demo');
        $woocommerce_product_ids = isset($settings['woocommerce_product_ids']) ? $settings['woocommerce_product_ids'] : array(483, 486, 487);
        
        // Guardar configuración
        if (isset($_POST['mhtp_ts_save_settings']) && check_admin_referer('mhtp_ts_settings_nonce')) {
            // Obtener valores del formulario
            $default_expiry_days = isset($_POST['default_expiry_days']) ? absint($_POST['default_expiry_days']) : 30;
            $default_category = isset($_POST['default_category']) ? sanitize_text_field($_POST['default_category']) : 'test';
            
            // Obtener categorías
            $categories = array();
            if (isset($_POST['categories']) && is_array($_POST['categories'])) {
                foreach ($_POST['categories'] as $category) {
                    $categories[] = sanitize_text_field($category);
                }
            }
            
            // Si no hay categorías, añadir una por defecto
            if (empty($categories)) {
                $categories = array('test');
            }
            
            // Obtener IDs de productos de WooCommerce
            $woocommerce_product_ids = array();
            if (isset($_POST['woocommerce_product_ids']) && is_array($_POST['woocommerce_product_ids'])) {
                foreach ($_POST['woocommerce_product_ids'] as $product_id) {
                    $woocommerce_product_ids[] = absint($product_id);
                }
            }
            
            // Guardar configuración
            $settings = array(
                'default_expiry_days' => $default_expiry_days,
                'default_category' => $default_category,
                'categories' => $categories,
                'woocommerce_product_ids' => $woocommerce_product_ids
            );
            
            update_option('mhtp_ts_settings', $settings);
            
            // Mostrar mensaje de éxito
            add_settings_error(
                'mhtp_ts_settings',
                'mhtp_ts_settings_saved',
                __('Configuración guardada correctamente.', 'mhtp-test-sessions'),
                'updated'
            );
        }
        
        // Incluir template
        include MHTP_TS_PLUGIN_DIR . 'admin/templates/settings-page.php';
    }

    /**
     * Manejar solicitud AJAX para añadir sesiones
     */
    public function ajax_add_sessions() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mhtp_ts_admin_nonce')) {
            wp_send_json_error(array(
                'message' => __('Error de seguridad. Por favor, recarga la página e inténtalo de nuevo.', 'mhtp-test-sessions')
            ));
        }
        
        // Verificar permisos
        if (!current_user_can('manage_test_sessions')) {
            wp_send_json_error(array(
                'message' => __('No tienes permisos para realizar esta acción.', 'mhtp-test-sessions')
            ));
        }
        
        // Obtener datos del formulario
        $user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
        $quantity = isset($_POST['quantity']) ? absint($_POST['quantity']) : 0;
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : 'test';
        $expiry_days = isset($_POST['expiry_days']) && $_POST['expiry_days'] !== '' ? absint($_POST['expiry_days']) : 0;
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
        
        // Validar datos
        if ($user_id <= 0) {
            wp_send_json_error(array(
                'message' => __('Usuario no válido.', 'mhtp-test-sessions')
            ));
        }
        
        if ($quantity <= 0) {
            wp_send_json_error(array(
                'message' => __('La cantidad debe ser mayor que cero.', 'mhtp-test-sessions')
            ));
        }
        
        // Verificar si el usuario existe
        $user = get_userdata($user_id);
        if (!$user) {
            wp_send_json_error(array(
                'message' => __('El usuario seleccionado no existe.', 'mhtp-test-sessions')
            ));
        }
        
        // Calcular fecha de caducidad
        $expiry_date = null;
        if ($expiry_days > 0) {
            $expiry_date = date('Y-m-d H:i:s', strtotime('+' . $expiry_days . ' days'));
        }
        
        // Añadir sesiones
        $db = MHTP_TS_DB::get_instance();
        $result = $db->add_test_sessions($user_id, $quantity, $category, $expiry_date, $notes);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => sprintf(__('Se han añadido %d sesiones a %s correctamente.', 'mhtp-test-sessions'), $quantity, $user->display_name)
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Ha ocurrido un error al añadir las sesiones. Por favor, inténtalo de nuevo.', 'mhtp-test-sessions')
            ));
        }
    }

    /**
     * Manejar solicitud AJAX para eliminar sesión
     */
    public function ajax_delete_session() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mhtp_ts_admin_nonce')) {
            wp_send_json_error(array(
                'message' => __('Error de seguridad. Por favor, recarga la página e inténtalo de nuevo.', 'mhtp-test-sessions')
            ));
        }
        
        // Verificar permisos
        if (!current_user_can('manage_test_sessions')) {
            wp_send_json_error(array(
                'message' => __('No tienes permisos para realizar esta acción.', 'mhtp-test-sessions')
            ));
        }
        
        // Obtener ID de sesión
        $session_id = isset($_POST['session_id']) ? absint($_POST['session_id']) : 0;
        
        // Validar ID de sesión
        if ($session_id <= 0) {
            wp_send_json_error(array(
                'message' => __('ID de sesión no válido.', 'mhtp-test-sessions')
            ));
        }
        
        // Eliminar sesión
        $db = MHTP_TS_DB::get_instance();
        $result = $db->delete_test_session($session_id);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Sesión eliminada correctamente.', 'mhtp-test-sessions')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Ha ocurrido un error al eliminar la sesión. Por favor, inténtalo de nuevo.', 'mhtp-test-sessions')
            ));
        }
    }
}
