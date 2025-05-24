<?php
/**
 * Clase para gestionar la integración con WooCommerce
 *
 * @package MHTP_Test_Sessions
 */

// Si este archivo es llamado directamente, abortar
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar la integración con WooCommerce
 */
class MHTP_TS_WooCommerce {

    /**
     * Instancia única de la clase
     */
    private static $instance = null;

    /**
     * IDs de productos de sesiones
     */
    private $session_product_ids;

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
        // Obtener IDs de productos de sesiones
        $settings = get_option('mhtp_ts_settings', array());
        $this->session_product_ids = isset($settings['woocommerce_product_ids']) ? $settings['woocommerce_product_ids'] : array(483, 486, 487);
        
        // Añadir hooks de WooCommerce
        add_action('woocommerce_order_status_completed', array($this, 'process_completed_order'));
        
        // Añadir filtro para mostrar tipo de sesión en Mi Cuenta
        add_filter('woocommerce_my_account_my_orders_actions', array($this, 'add_view_sessions_action'), 10, 2);
        
        // Añadir endpoint para ver sesiones
        add_action('init', array($this, 'add_endpoints'));
        add_filter('query_vars', array($this, 'add_query_vars'), 0);
        add_filter('woocommerce_account_menu_items', array($this, 'add_menu_items'));
        add_action('woocommerce_account_view-sessions_endpoint', array($this, 'view_sessions_content'));
    }

    /**
     * Procesar orden completada
     */
    public function process_completed_order($order_id) {
        $order = wc_get_order($order_id);
        $user_id = $order->get_user_id();
        
        if (!$user_id) {
            return;
        }
        
        // Verificar si la orden contiene productos de sesiones
        $contains_session_product = false;
        $total_sessions = 0;
        
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            
            if (in_array($product_id, $this->session_product_ids)) {
                $contains_session_product = true;
                $quantity = $item->get_quantity();
                
                // Calcular número de sesiones según el producto
                switch ($product_id) {
                    case 483: // 1 Sesión
                        $total_sessions += $quantity * 1;
                        break;
                    case 486: // 5 Sesiones
                        $total_sessions += $quantity * 5;
                        break;
                    case 487: // 10 Sesiones
                        $total_sessions += $quantity * 10;
                        break;
                    default:
                        // Para otros productos, verificar si tienen metadatos de sesiones
                        $sessions_meta = get_post_meta($product_id, '_mhtp_sessions_count', true);
                        if ($sessions_meta) {
                            $total_sessions += $quantity * intval($sessions_meta);
                        }
                }
            }
        }
        
        // Si la orden contiene productos de sesiones, actualizar metadatos de usuario
        if ($contains_session_product && $total_sessions > 0) {
            // Obtener sesiones actuales
            $current_sessions = get_user_meta($user_id, 'mhtp_available_sessions', true);
            
            if (empty($current_sessions)) {
                $current_sessions = 0;
            }
            
            // Añadir nuevas sesiones
            $new_total = $current_sessions + $total_sessions;
            
            // Actualizar metadatos de usuario
            update_user_meta($user_id, 'mhtp_available_sessions', $new_total);
            
            // Registrar en el log
            $this->log_woocommerce_session_purchase($user_id, $order_id, $total_sessions);
        }
    }

    /**
     * Registrar compra de sesiones en el log
     */
    private function log_woocommerce_session_purchase($user_id, $order_id, $sessions_count) {
        // Implementar sistema de log si es necesario
    }

    /**
     * Añadir acción para ver sesiones en órdenes
     */
    public function add_view_sessions_action($actions, $order) {
        // Verificar si la orden contiene productos de sesiones
        $contains_session_product = false;
        
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            
            if (in_array($product_id, $this->session_product_ids)) {
                $contains_session_product = true;
                break;
            }
        }
        
        // Si la orden contiene productos de sesiones, añadir acción
        if ($contains_session_product && $order->get_status() === 'completed') {
            $actions['view_sessions'] = array(
                'url' => wc_get_endpoint_url('view-sessions', '', wc_get_page_permalink('myaccount')),
                'name' => __('Ver Sesiones', 'mhtp-test-sessions')
            );
        }
        
        return $actions;
    }

    /**
     * Añadir endpoints
     */
    public function add_endpoints() {
        add_rewrite_endpoint('view-sessions', EP_ROOT | EP_PAGES);
        
        // Actualizar rewrite rules si es necesario
        $option = get_option('mhtp_ts_flush_rewrite_rules', false);
        
        if ($option) {
            flush_rewrite_rules();
            delete_option('mhtp_ts_flush_rewrite_rules');
        }
    }

    /**
     * Añadir query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'view-sessions';
        return $vars;
    }

    /**
     * Añadir elementos al menú de Mi Cuenta
     */
    public function add_menu_items($items) {
        // Añadir después de pedidos
        $new_items = array();
        
        foreach ($items as $key => $value) {
            $new_items[$key] = $value;
            
            if ($key === 'orders') {
                $new_items['view-sessions'] = __('Mis Sesiones', 'mhtp-test-sessions');
            }
        }
        
        return $new_items;
    }

    /**
     * Contenido de la página de sesiones
     */
    public function view_sessions_content() {
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return;
        }
        
        // Obtener sesiones disponibles
        $db = MHTP_TS_DB::get_instance();
        $test_sessions = $db->get_user_test_sessions($user_id);
        
        // Obtener total de sesiones disponibles
        $total_available = get_user_meta($user_id, 'mhtp_available_sessions', true);
        
        if (empty($total_available)) {
            $total_available = 0;
        }
        
        // Incluir template
        include MHTP_TS_PLUGIN_DIR . 'public/templates/view-sessions.php';
    }
}
