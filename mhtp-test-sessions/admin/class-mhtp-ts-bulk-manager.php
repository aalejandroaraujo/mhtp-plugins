<?php
/**
 * Clase para gestionar la asignación masiva de sesiones
 *
 * @package MHTP_Test_Sessions
 */

// Si este archivo es llamado directamente, abortar
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar la asignación masiva de sesiones
 */
class MHTP_TS_Bulk_Manager {

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
        // Registrar AJAX handlers
        add_action('wp_ajax_mhtp_ts_get_filtered_users', array($this, 'ajax_get_filtered_users'));
        add_action('wp_ajax_mhtp_ts_bulk_add_sessions', array($this, 'ajax_bulk_add_sessions'));
        add_action('wp_ajax_mhtp_ts_process_csv', array($this, 'ajax_process_csv'));
    }

    /**
     * AJAX: Obtener usuarios filtrados
     */
    public function ajax_get_filtered_users() {
        // Verificar nonce
        check_ajax_referer('mhtp_ts_admin_nonce', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('manage_test_sessions')) {
            wp_send_json_error(array('message' => __('No tienes permisos para realizar esta acción', 'mhtp-test-sessions')));
        }
        
        // Obtener y validar datos
        $role = isset($_POST['role']) ? sanitize_text_field($_POST['role']) : '';
        $registration_date = isset($_POST['registration_date']) ? sanitize_text_field($_POST['registration_date']) : '';
        $has_purchased = isset($_POST['has_purchased']) ? (bool) $_POST['has_purchased'] : false;
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        
        // Construir argumentos para get_users
        $args = array(
            'number' => 100, // Limitar a 100 usuarios por consulta
            'orderby' => 'display_name',
            'order' => 'ASC'
        );
        
        // Filtrar por rol
        if (!empty($role)) {
            $args['role'] = $role;
        }
        
        // Filtrar por fecha de registro
        if (!empty($registration_date)) {
            $date_parts = explode('-', $registration_date);
            if (count($date_parts) === 3) {
                $args['date_query'] = array(
                    array(
                        'after' => array(
                            'year' => $date_parts[0],
                            'month' => $date_parts[1],
                            'day' => $date_parts[2]
                        ),
                        'inclusive' => true
                    )
                );
            }
        }
        
        // Filtrar por búsqueda
        if (!empty($search)) {
            $args['search'] = '*' . $search . '*';
            $args['search_columns'] = array('user_login', 'user_email', 'display_name');
        }
        
        // Obtener usuarios
        $users = get_users($args);
        
        // Filtrar por compras si es necesario
        if ($has_purchased && function_exists('wc_get_orders')) {
            $filtered_users = array();
            
            foreach ($users as $user) {
                $args = array(
                    'customer_id' => $user->ID,
                    'status' => array('wc-completed'),
                    'limit' => 1
                );
                
                $orders = wc_get_orders($args);
                
                if (count($orders) > 0) {
                    $filtered_users[] = $user;
                }
            }
            
            $users = $filtered_users;
        }
        
        // Preparar datos para respuesta
        $response_data = array();
        
        foreach ($users as $user) {
            $response_data[] = array(
                'id' => $user->ID,
                'display_name' => $user->display_name,
                'email' => $user->user_email,
                'registered' => date('Y-m-d', strtotime($user->user_registered))
            );
        }
        
        wp_send_json_success(array(
            'users' => $response_data,
            'total' => count($response_data)
        ));
    }

    /**
     * AJAX: Añadir sesiones en masa
     */
    public function ajax_bulk_add_sessions() {
        // Verificar nonce
        check_ajax_referer('mhtp_ts_admin_nonce', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('manage_test_sessions')) {
            wp_send_json_error(array('message' => __('No tienes permisos para realizar esta acción', 'mhtp-test-sessions')));
        }
        
        // Obtener y validar datos
        $user_ids = isset($_POST['user_ids']) ? array_map('absint', $_POST['user_ids']) : array();
        $quantity = isset($_POST['quantity']) ? absint($_POST['quantity']) : 0;
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : 'test';
        $expiry_days = isset($_POST['expiry_days']) ? absint($_POST['expiry_days']) : 30;
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
        
        if (empty($user_ids) || $quantity <= 0) {
            wp_send_json_error(array('message' => __('Datos inválidos', 'mhtp-test-sessions')));
        }
        
        // Añadir sesiones en masa
        $db = MHTP_TS_DB::get_instance();
        $results = $db->add_bulk_test_sessions($user_ids, $quantity, $category, $expiry_days, $notes);
        
        wp_send_json_success(array(
            'message' => sprintf(
                __('Se han añadido sesiones a %d usuarios correctamente. %d usuarios fallaron.', 'mhtp-test-sessions'),
                $results['success'],
                $results['failed']
            ),
            'results' => $results
        ));
    }

    /**
     * AJAX: Procesar archivo CSV
     */
    public function ajax_process_csv() {
        // Verificar nonce
        check_ajax_referer('mhtp_ts_admin_nonce', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('manage_test_sessions')) {
            wp_send_json_error(array('message' => __('No tienes permisos para realizar esta acción', 'mhtp-test-sessions')));
        }
        
        // Verificar archivo
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array('message' => __('Error al subir el archivo', 'mhtp-test-sessions')));
        }
        
        // Obtener archivo
        $file = $_FILES['csv_file']['tmp_name'];
        
        // Abrir archivo
        $handle = fopen($file, 'r');
        
        if (!$handle) {
            wp_send_json_error(array('message' => __('Error al abrir el archivo', 'mhtp-test-sessions')));
        }
        
        // Leer encabezados
        $headers = fgetcsv($handle);
        
        // Verificar encabezados
        if (!in_array('email', $headers) && !in_array('user_id', $headers)) {
            fclose($handle);
            wp_send_json_error(array('message' => __('El archivo CSV debe contener una columna "email" o "user_id"', 'mhtp-test-sessions')));
        }
        
        // Procesar filas
        $users = array();
        $errors = array();
        
        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($headers, $row);
            
            // Buscar usuario por email o ID
            $user = null;
            
            if (isset($data['user_id']) && !empty($data['user_id'])) {
                $user = get_user_by('id', $data['user_id']);
            } elseif (isset($data['email']) && !empty($data['email'])) {
                $user = get_user_by('email', $data['email']);
            }
            
            if ($user) {
                $users[] = array(
                    'id' => $user->ID,
                    'display_name' => $user->display_name,
                    'email' => $user->user_email
                );
            } else {
                $errors[] = sprintf(
                    __('No se encontró el usuario: %s', 'mhtp-test-sessions'),
                    isset($data['email']) ? $data['email'] : $data['user_id']
                );
            }
        }
        
        fclose($handle);
        
        wp_send_json_success(array(
            'users' => $users,
            'errors' => $errors,
            'total' => count($users)
        ));
    }
}
