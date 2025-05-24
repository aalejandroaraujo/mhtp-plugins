<?php
/**
 * Clase para gestionar la base de datos del plugin
 *
 * @package MHTP_Test_Sessions
 */

// Si este archivo es llamado directamente, abortar
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar la base de datos
 */
class MHTP_TS_DB {

    /**
     * Instancia única de la clase
     */
    private static $instance = null;

    /**
     * Nombre de la tabla de sesiones
     */
    private $sessions_table;

    /**
     * Nombre de la tabla de uso de sesiones
     */
    private $usage_table;

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
        global $wpdb;
        
        // Definir nombres de tablas
        $this->sessions_table = $wpdb->prefix . 'mhtp_test_sessions';
        $this->usage_table = $wpdb->prefix . 'mhtp_session_usage';
        
        // Programar limpieza diaria de sesiones caducadas
        if (!wp_next_scheduled('mhtp_ts_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'mhtp_ts_daily_cleanup');
        }
        
        // Añadir acción para limpieza
        add_action('mhtp_ts_daily_cleanup', array($this, 'cleanup_expired_sessions'));
    }

    /**
     * Crear tablas en la base de datos
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sessions_table = $wpdb->prefix . 'mhtp_test_sessions';
        $usage_table = $wpdb->prefix . 'mhtp_session_usage';
        
        // SQL para crear tabla de sesiones
        $sessions_sql = "CREATE TABLE $sessions_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            category varchar(50) NOT NULL DEFAULT 'test',
            quantity int(11) NOT NULL DEFAULT 1,
            expiry_date datetime DEFAULT NULL,
            created_at datetime NOT NULL,
            created_by bigint(20) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            notes text DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY status (status),
            KEY expiry_date (expiry_date)
        ) $charset_collate;";
        
        // SQL para crear tabla de uso
        $usage_sql = "CREATE TABLE $usage_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            used_at datetime NOT NULL,
            expert_id bigint(20) DEFAULT NULL,
            duration int(11) DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY session_id (session_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        // Incluir archivo para dbDelta
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Crear tablas
        dbDelta($sessions_sql);
        dbDelta($usage_sql);
    }

    /**
     * Obtener tabla de sesiones
     */
    public function get_sessions_table() {
        return $this->sessions_table;
    }

    /**
     * Obtener tabla de uso
     */
    public function get_usage_table() {
        return $this->usage_table;
    }

    /**
     * Añadir sesiones de prueba a un usuario
     */
    public function add_test_sessions($user_id, $quantity, $category = 'test', $expiry_days = 30, $notes = '', $created_by = 0) {
        global $wpdb;
        
        // Validar datos
        $user_id = absint($user_id);
        $quantity = absint($quantity);
        $category = sanitize_text_field($category);
        $expiry_days = absint($expiry_days);
        $notes = sanitize_textarea_field($notes);
        
        if ($created_by <= 0) {
            $created_by = get_current_user_id();
        }
        
        // Calcular fecha de expiración
        $expiry_date = null;
        if ($expiry_days > 0) {
            $expiry_date = date('Y-m-d H:i:s', strtotime("+{$expiry_days} days"));
        }
        
        // Insertar en la base de datos
        $result = $wpdb->insert(
            $this->sessions_table,
            array(
                'user_id' => $user_id,
                'category' => $category,
                'quantity' => $quantity,
                'expiry_date' => $expiry_date,
                'created_at' => current_time('mysql'),
                'created_by' => $created_by,
                'status' => 'active',
                'notes' => $notes
            ),
            array('%d', '%s', '%d', '%s', '%s', '%d', '%s', '%s')
        );
        
        if ($result) {
            // Actualizar metadatos de usuario para compatibilidad
            $this->update_user_session_count($user_id);
            
            // Registrar en el log
            $this->log_activity('add', $wpdb->insert_id, $user_id, $quantity, $category);
            
            return $wpdb->insert_id;
        }
        
        return false;
    }

    /**
     * Añadir sesiones de prueba a múltiples usuarios
     */
    public function add_bulk_test_sessions($user_ids, $quantity, $category = 'test', $expiry_days = 30, $notes = '') {
        $results = array(
            'success' => 0,
            'failed' => 0,
            'user_ids' => array()
        );
        
        foreach ($user_ids as $user_id) {
            $result = $this->add_test_sessions($user_id, $quantity, $category, $expiry_days, $notes);
            
            if ($result) {
                $results['success']++;
                $results['user_ids'][] = $user_id;
            } else {
                $results['failed']++;
            }
        }
        
        return $results;
    }

    /**
     * Obtener sesiones de prueba de un usuario
     */
    public function get_user_test_sessions($user_id, $status = 'active') {
        global $wpdb;
        
        $user_id = absint($user_id);
        $status = sanitize_text_field($status);
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$this->sessions_table} 
            WHERE user_id = %d 
            AND status = %s 
            AND (expiry_date IS NULL OR expiry_date > %s)
            ORDER BY created_at DESC",
            $user_id,
            $status,
            current_time('mysql')
        );
        
        return $wpdb->get_results($query);
    }

    /**
     * Contar sesiones de prueba disponibles para un usuario
     */
    public function count_user_available_sessions($user_id) {
        global $wpdb;
        
        $user_id = absint($user_id);
        
        $query = $wpdb->prepare(
            "SELECT SUM(quantity) FROM {$this->sessions_table} 
            WHERE user_id = %d 
            AND status = 'active' 
            AND (expiry_date IS NULL OR expiry_date > %s)",
            $user_id,
            current_time('mysql')
        );
        
        $count = $wpdb->get_var($query);
        
        return $count ? absint($count) : 0;
    }

    /**
     * Actualizar contador de sesiones en metadatos de usuario
     */
    public function update_user_session_count($user_id) {
        $count = $this->count_user_available_sessions($user_id);
        
        // Obtener sesiones de WooCommerce
        $wc_sessions = $this->get_woocommerce_sessions($user_id);
        
        // Total de sesiones disponibles
        $total_sessions = $count + $wc_sessions;
        
        // Actualizar metadatos de usuario
        update_user_meta($user_id, 'mhtp_available_sessions', $total_sessions);
        
        return $total_sessions;
    }

    /**
     * Obtener sesiones de WooCommerce
     */
    private function get_woocommerce_sessions($user_id) {
        // IDs de productos de sesiones
        $session_product_ids = array(483, 486, 487);
        
        // Contar sesiones de WooCommerce
        $wc_sessions = 0;
        
        // Verificar si WooCommerce está activo
        if (function_exists('wc_get_orders')) {
            $args = array(
                'customer_id' => $user_id,
                'status' => array('wc-completed'),
                'limit' => -1
            );
            
            $orders = wc_get_orders($args);
            
            foreach ($orders as $order) {
                foreach ($order->get_items() as $item) {
                    $product_id = $item->get_product_id();
                    
                    if (in_array($product_id, $session_product_ids)) {
                        $quantity = $item->get_quantity();
                        
                        // Añadir sesiones según el producto
                        switch ($product_id) {
                            case 483: // 1 Sesión
                                $wc_sessions += $quantity * 1;
                                break;
                            case 486: // 5 Sesiones
                                $wc_sessions += $quantity * 5;
                                break;
                            case 487: // 10 Sesiones
                                $wc_sessions += $quantity * 10;
                                break;
                        }
                    }
                }
            }
        }
        
        return $wc_sessions;
    }

    /**
     * Marcar sesión como usada
     */
    public function use_session($user_id, $expert_id = 0) {
        global $wpdb;
        
        $user_id = absint($user_id);
        $expert_id = absint($expert_id);
        
        // Primero buscar sesiones de prueba activas
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->sessions_table} 
            WHERE user_id = %d 
            AND status = 'active' 
            AND quantity > 0
            AND (expiry_date IS NULL OR expiry_date > %s)
            ORDER BY expiry_date ASC, created_at ASC
            LIMIT 1",
            $user_id,
            current_time('mysql')
        ));
        
        if ($session) {
            // Actualizar cantidad de sesiones
            $new_quantity = $session->quantity - 1;
            $new_status = $new_quantity > 0 ? 'active' : 'used';
            
            $wpdb->update(
                $this->sessions_table,
                array(
                    'quantity' => $new_quantity,
                    'status' => $new_status
                ),
                array('id' => $session->id),
                array('%d', '%s'),
                array('%d')
            );
            
            // Registrar uso
            $wpdb->insert(
                $this->usage_table,
                array(
                    'session_id' => $session->id,
                    'user_id' => $user_id,
                    'used_at' => current_time('mysql'),
                    'expert_id' => $expert_id
                ),
                array('%d', '%d', '%s', '%d')
            );
            
            // Actualizar metadatos de usuario
            $this->update_user_session_count($user_id);
            
            // Registrar en el log
            $this->log_activity('use', $session->id, $user_id, 1, $session->category);
            
            return true;
        }
        
        return false;
    }

    /**
     * Limpiar sesiones caducadas
     */
    public function cleanup_expired_sessions() {
        global $wpdb;
        
        // Marcar como caducadas las sesiones expiradas
        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->sessions_table} 
            SET status = 'expired' 
            WHERE status = 'active' 
            AND expiry_date IS NOT NULL 
            AND expiry_date < %s",
            current_time('mysql')
        ));
        
        // Actualizar metadatos de usuarios afectados
        $affected_users = $wpdb->get_col(
            "SELECT DISTINCT user_id FROM {$this->sessions_table} 
            WHERE status = 'expired'"
        );
        
        foreach ($affected_users as $user_id) {
            $this->update_user_session_count($user_id);
        }
        
        // Registrar en el log
        $this->log_activity('cleanup', 0, 0, count($affected_users), 'system');
    }

    /**
     * Registrar actividad en el log
     */
    private function log_activity($action, $session_id, $user_id, $quantity, $category) {
        // Implementar sistema de log si es necesario
    }

    /**
     * Obtener estadísticas de uso
     */
    public function get_usage_statistics($period = 'month') {
        global $wpdb;
        
        $stats = array(
            'total_sessions_added' => 0,
            'total_sessions_used' => 0,
            'total_sessions_expired' => 0,
            'total_users_with_sessions' => 0,
            'conversion_rate' => 0,
            'average_time_to_use' => 0,
            'usage_by_category' => array(),
            'usage_by_day' => array()
        );
        
        // Determinar fecha de inicio según el período
        $start_date = '';
        switch ($period) {
            case 'week':
                $start_date = date('Y-m-d H:i:s', strtotime('-1 week'));
                break;
            case 'month':
                $start_date = date('Y-m-d H:i:s', strtotime('-1 month'));
                break;
            case 'year':
                $start_date = date('Y-m-d H:i:s', strtotime('-1 year'));
                break;
            default:
                $start_date = date('Y-m-d H:i:s', strtotime('-1 month'));
        }
        
        // Total de sesiones añadidas
        $stats['total_sessions_added'] = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(quantity) FROM {$this->sessions_table} 
            WHERE created_at >= %s",
            $start_date
        ));
        
        // Total de sesiones usadas
        $stats['total_sessions_used'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->usage_table} 
            WHERE used_at >= %s",
            $start_date
        ));
        
        // Total de sesiones caducadas
        $stats['total_sessions_expired'] = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(quantity) FROM {$this->sessions_table} 
            WHERE status = 'expired' 
            AND expiry_date >= %s",
            $start_date
        ));
        
        // Total de usuarios con sesiones
        $stats['total_users_with_sessions'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM {$this->sessions_table} 
            WHERE created_at >= %s",
            $start_date
        ));
        
        // Uso por categoría
        $categories = $wpdb->get_results($wpdb->prepare(
            "SELECT category, SUM(quantity) as total 
            FROM {$this->sessions_table} 
            WHERE created_at >= %s 
            GROUP BY category",
            $start_date
        ));
        
        foreach ($categories as $cat) {
            $stats['usage_by_category'][$cat->category] = $cat->total;
        }
        
        // Calcular tasa de conversión (usuarios que compraron después de usar sesiones gratuitas)
        // Esta es una implementación simplificada, se puede mejorar según necesidades específicas
        if (function_exists('wc_get_orders') && $stats['total_users_with_sessions'] > 0) {
            $test_users = $wpdb->get_col($wpdb->prepare(
                "SELECT DISTINCT user_id FROM {$this->sessions_table} 
                WHERE created_at >= %s",
                $start_date
            ));
            
            $converted_users = 0;
            
            foreach ($test_users as $user_id) {
                $args = array(
                    'customer_id' => $user_id,
                    'status' => array('wc-completed'),
                    'date_created' => '>' . $start_date
                );
                
                $orders = wc_get_orders($args);
                
                if (count($orders) > 0) {
                    $converted_users++;
                }
            }
            
            $stats['conversion_rate'] = ($converted_users / $stats['total_users_with_sessions']) * 100;
        }
        
        return $stats;
    }
}
