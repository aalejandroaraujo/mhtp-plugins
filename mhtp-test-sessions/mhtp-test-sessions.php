<?php
/**
 * Plugin Name: MHTP Test Sessions Manager
 * Plugin URI: https://gabinetedeorientacion.com
 * Description: Plugin para gestionar sesiones de prueba para usuarios
 * Version: 1.0.3
 * Author: Araujo Innovations
 * Author URI: https://gabinetedeorientacion.com
 * Text Domain: mhtp-test-sessions
 * Domain Path: /languages
 */

// Si este archivo es llamado directamente, abortar
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes
define('MHTP_TS_VERSION', '1.0.3');
define('MHTP_TS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MHTP_TS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MHTP_TS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Clase principal del plugin
 */
class MHTP_Test_Sessions {

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
        // Registrar hooks de activación y desactivación
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Cargar dependencias
        $this->load_dependencies();
        
        // Inicializar componentes
        $this->init();
        
        // Registrar hooks de administración
        add_action('admin_init', array($this, 'register_capabilities'));
    }

    /**
     * Cargar dependencias
     */
    private function load_dependencies() {
        // Incluir archivos de clases
        require_once MHTP_TS_PLUGIN_DIR . 'includes/class-mhtp-ts-db.php';
        require_once MHTP_TS_PLUGIN_DIR . 'includes/class-mhtp-ts-session.php';
        require_once MHTP_TS_PLUGIN_DIR . 'includes/class-mhtp-ts-user.php';
        require_once MHTP_TS_PLUGIN_DIR . 'includes/integrations/class-mhtp-ts-woocommerce.php';
        require_once MHTP_TS_PLUGIN_DIR . 'admin/class-mhtp-ts-admin.php';
        require_once MHTP_TS_PLUGIN_DIR . 'admin/class-mhtp-ts-bulk-manager.php';
        require_once MHTP_TS_PLUGIN_DIR . 'admin/class-mhtp-ts-statistics.php';
        require_once MHTP_TS_PLUGIN_DIR . 'public/class-mhtp-ts-public.php';
    }

    /**
     * Inicializar componentes
     */
    private function init() {
        // Inicializar base de datos
        MHTP_TS_DB::get_instance();
        
        // Inicializar administración
        MHTP_TS_Admin::get_instance();
        
        // Inicializar gestor de asignación masiva
        MHTP_TS_Bulk_Manager::get_instance();
        
        // Inicializar estadísticas
        MHTP_TS_Statistics::get_instance();
        
        // Inicializar integración con WooCommerce
        MHTP_TS_WooCommerce::get_instance();
        
        // Inicializar frontend
        MHTP_TS_Public::get_instance();
    }

    /**
     * Activar plugin
     */
    public function activate() {
        // Crear tablas de base de datos
        $db = MHTP_TS_DB::get_instance();
        $db->create_tables();
        
        // Registrar capacidades
        $this->register_capabilities();
        
        // Establecer configuración por defecto
        $this->set_default_settings();
        
        // Limpiar caché de rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Desactivar plugin
     */
    public function deactivate() {
        // Limpiar caché de rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Registrar capacidades
     */
    public function register_capabilities() {
        // Obtener rol de administrador
        $admin_role = get_role('administrator');
        
        if ($admin_role) {
            // Añadir capacidades
            $admin_role->add_cap('manage_test_sessions');
            $admin_role->add_cap('view_test_sessions_stats');
        }
    }

    /**
     * Establecer configuración por defecto
     */
    private function set_default_settings() {
        // Verificar si ya existe configuración
        if (get_option('mhtp_ts_settings')) {
            return;
        }
        
        // Configuración por defecto
        $default_settings = array(
            'default_expiry_days' => 30,
            'default_category' => 'test',
            'categories' => array('test', 'promo', 'demo'),
            'woocommerce_product_ids' => array(483, 486, 487)
        );
        
        // Guardar configuración
        update_option('mhtp_ts_settings', $default_settings);
    }
}

// Inicializar plugin
function mhtp_test_sessions() {
    return MHTP_Test_Sessions::get_instance();
}

// Ejecutar plugin
mhtp_test_sessions();
