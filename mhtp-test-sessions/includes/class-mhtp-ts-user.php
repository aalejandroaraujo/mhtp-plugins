<?php
/**
 * Clase para gestionar usuarios y sus sesiones
 *
 * @package MHTP_Test_Sessions
 */

// Si este archivo es llamado directamente, abortar
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar usuarios y sus sesiones
 */
class MHTP_TS_User {

    /**
     * ID del usuario
     */
    private $user_id;

    /**
     * Datos del usuario
     */
    private $user_data;

    /**
     * Constructor
     */
    public function __construct($user_id) {
        $this->user_id = $user_id;
        $this->user_data = get_userdata($user_id);
    }

    /**
     * Obtener ID del usuario
     */
    public function get_id() {
        return $this->user_id;
    }

    /**
     * Obtener nombre del usuario
     */
    public function get_name() {
        if ($this->user_data) {
            return $this->user_data->display_name;
        }
        return '';
    }

    /**
     * Obtener email del usuario
     */
    public function get_email() {
        if ($this->user_data) {
            return $this->user_data->user_email;
        }
        return '';
    }

    /**
     * Obtener fecha de registro del usuario
     */
    public function get_registered_date() {
        if ($this->user_data) {
            return $this->user_data->user_registered;
        }
        return '';
    }

    /**
     * Obtener sesiones disponibles del usuario
     */
    public function get_available_sessions() {
        $db = MHTP_TS_DB::get_instance();
        return $db->get_user_test_sessions($this->user_id);
    }

    /**
     * Obtener total de sesiones disponibles
     */
    public function get_total_available_sessions() {
        $total = get_user_meta($this->user_id, 'mhtp_available_sessions', true);
        
        if (empty($total)) {
            $total = 0;
        }
        
        return $total;
    }

    /**
     * Actualizar total de sesiones disponibles
     */
    public function update_total_available_sessions() {
        $db = MHTP_TS_DB::get_instance();
        $sessions = $db->get_user_test_sessions($this->user_id);
        
        $total = 0;
        
        foreach ($sessions as $session) {
            if ($session['status'] === 'active') {
                $total += $session['quantity'];
            }
        }
        
        update_user_meta($this->user_id, 'mhtp_available_sessions', $total);
        
        return $total;
    }

    /**
     * Añadir sesiones al usuario
     */
    public function add_sessions($quantity, $category = 'test', $expiry_days = 30, $notes = '') {
        $db = MHTP_TS_DB::get_instance();
        $session_id = $db->add_test_sessions($this->user_id, $quantity, $category, $expiry_days, $notes);
        
        if ($session_id) {
            $this->update_total_available_sessions();
            return $session_id;
        }
        
        return false;
    }

    /**
     * Usar una sesión
     */
    public function use_session() {
        $db = MHTP_TS_DB::get_instance();
        $sessions = $db->get_user_test_sessions($this->user_id);
        
        // Buscar la primera sesión activa
        foreach ($sessions as $session) {
            if ($session['status'] === 'active') {
                // Crear objeto de sesión
                $session_obj = MHTP_TS_Session::get_by_id($session['id']);
                
                if ($session_obj) {
                    // Marcar como usada
                    $session_obj->mark_as_used();
                    
                    // Actualizar total de sesiones
                    $this->update_total_available_sessions();
                    
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Verificar si el usuario tiene sesiones disponibles
     */
    public function has_available_sessions() {
        return $this->get_total_available_sessions() > 0;
    }

    /**
     * Obtener historial de uso de sesiones
     */
    public function get_sessions_usage_history() {
        global $wpdb;
        $db = MHTP_TS_DB::get_instance();
        $table = $db->get_usage_table();
        
        $history = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE user_id = %d ORDER BY used_at DESC",
                $this->user_id
            ),
            ARRAY_A
        );
        
        return $history;
    }

    /**
     * Verificar si el usuario ha realizado compras
     */
    public function has_purchased() {
        if (function_exists('wc_get_orders')) {
            $args = array(
                'customer_id' => $this->user_id,
                'status' => array('wc-completed'),
                'limit' => 1
            );
            
            $orders = wc_get_orders($args);
            
            return count($orders) > 0;
        }
        
        return false;
    }

    /**
     * Verificar si el usuario ha comprado sesiones
     */
    public function has_purchased_sessions() {
        if (function_exists('wc_get_orders')) {
            $args = array(
                'customer_id' => $this->user_id,
                'status' => array('wc-completed')
            );
            
            $orders = wc_get_orders($args);
            
            // Obtener IDs de productos de sesiones
            $settings = get_option('mhtp_ts_settings', array());
            $session_product_ids = isset($settings['woocommerce_product_ids']) ? $settings['woocommerce_product_ids'] : array(483, 486, 487);
            
            foreach ($orders as $order) {
                foreach ($order->get_items() as $item) {
                    $product_id = $item->get_product_id();
                    
                    if (in_array($product_id, $session_product_ids)) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }

    /**
     * Obtener tasa de conversión (si ha comprado después de usar sesiones gratuitas)
     */
    public function get_conversion_rate() {
        // Verificar si ha usado sesiones gratuitas
        $usage_history = $this->get_sessions_usage_history();
        
        if (empty($usage_history)) {
            return false;
        }
        
        // Verificar si ha comprado sesiones
        return $this->has_purchased_sessions();
    }
}
