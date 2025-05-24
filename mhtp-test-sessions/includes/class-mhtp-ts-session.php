<?php
/**
 * Clase para gestionar sesiones individuales
 *
 * @package MHTP_Test_Sessions
 */

// Si este archivo es llamado directamente, abortar
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar sesiones individuales
 */
class MHTP_TS_Session {

    /**
     * ID de la sesión
     */
    private $id;

    /**
     * ID del usuario
     */
    private $user_id;

    /**
     * Cantidad de sesiones
     */
    private $quantity;

    /**
     * Categoría de la sesión
     */
    private $category;

    /**
     * Fecha de creación
     */
    private $created_at;

    /**
     * Fecha de expiración
     */
    private $expiry_date;

    /**
     * Estado de la sesión
     */
    private $status;

    /**
     * Notas adicionales
     */
    private $notes;

    /**
     * Constructor
     */
    public function __construct($session_data = null) {
        if (is_array($session_data)) {
            $this->id = isset($session_data['id']) ? $session_data['id'] : 0;
            $this->user_id = isset($session_data['user_id']) ? $session_data['user_id'] : 0;
            $this->quantity = isset($session_data['quantity']) ? $session_data['quantity'] : 0;
            $this->category = isset($session_data['category']) ? $session_data['category'] : 'test';
            $this->created_at = isset($session_data['created_at']) ? $session_data['created_at'] : current_time('mysql');
            $this->expiry_date = isset($session_data['expiry_date']) ? $session_data['expiry_date'] : null;
            $this->status = isset($session_data['status']) ? $session_data['status'] : 'active';
            $this->notes = isset($session_data['notes']) ? $session_data['notes'] : '';
        }
    }

    /**
     * Obtener ID de la sesión
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Establecer ID de la sesión
     */
    public function set_id($id) {
        $this->id = $id;
    }

    /**
     * Obtener ID del usuario
     */
    public function get_user_id() {
        return $this->user_id;
    }

    /**
     * Establecer ID del usuario
     */
    public function set_user_id($user_id) {
        $this->user_id = $user_id;
    }

    /**
     * Obtener cantidad de sesiones
     */
    public function get_quantity() {
        return $this->quantity;
    }

    /**
     * Establecer cantidad de sesiones
     */
    public function set_quantity($quantity) {
        $this->quantity = $quantity;
    }

    /**
     * Obtener categoría de la sesión
     */
    public function get_category() {
        return $this->category;
    }

    /**
     * Establecer categoría de la sesión
     */
    public function set_category($category) {
        $this->category = $category;
    }

    /**
     * Obtener fecha de creación
     */
    public function get_created_at() {
        return $this->created_at;
    }

    /**
     * Establecer fecha de creación
     */
    public function set_created_at($created_at) {
        $this->created_at = $created_at;
    }

    /**
     * Obtener fecha de expiración
     */
    public function get_expiry_date() {
        return $this->expiry_date;
    }

    /**
     * Establecer fecha de expiración
     */
    public function set_expiry_date($expiry_date) {
        $this->expiry_date = $expiry_date;
    }

    /**
     * Obtener estado de la sesión
     */
    public function get_status() {
        return $this->status;
    }

    /**
     * Establecer estado de la sesión
     */
    public function set_status($status) {
        $this->status = $status;
    }

    /**
     * Obtener notas adicionales
     */
    public function get_notes() {
        return $this->notes;
    }

    /**
     * Establecer notas adicionales
     */
    public function set_notes($notes) {
        $this->notes = $notes;
    }

    /**
     * Guardar sesión en la base de datos
     */
    public function save() {
        global $wpdb;
        $db = MHTP_TS_DB::get_instance();
        $table = $db->get_sessions_table();
        
        $data = array(
            'user_id' => $this->user_id,
            'quantity' => $this->quantity,
            'category' => $this->category,
            'created_at' => $this->created_at,
            'expiry_date' => $this->expiry_date,
            'status' => $this->status,
            'notes' => $this->notes
        );
        
        $format = array(
            '%d',
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s'
        );
        
        if ($this->id > 0) {
            // Actualizar sesión existente
            $wpdb->update(
                $table,
                $data,
                array('id' => $this->id),
                $format,
                array('%d')
            );
            
            return $this->id;
        } else {
            // Insertar nueva sesión
            $wpdb->insert(
                $table,
                $data,
                $format
            );
            
            $this->id = $wpdb->insert_id;
            
            return $this->id;
        }
    }

    /**
     * Eliminar sesión de la base de datos
     */
    public function delete() {
        global $wpdb;
        $db = MHTP_TS_DB::get_instance();
        $table = $db->get_sessions_table();
        
        if ($this->id > 0) {
            return $wpdb->delete(
                $table,
                array('id' => $this->id),
                array('%d')
            );
        }
        
        return false;
    }

    /**
     * Verificar si la sesión ha expirado
     */
    public function is_expired() {
        if (empty($this->expiry_date)) {
            return false;
        }
        
        $now = new DateTime(current_time('mysql'));
        $expiry = new DateTime($this->expiry_date);
        
        return $now > $expiry;
    }

    /**
     * Marcar sesión como usada
     */
    public function mark_as_used($used_at = null) {
        if (empty($used_at)) {
            $used_at = current_time('mysql');
        }
        
        $this->status = 'used';
        $this->save();
        
        // Registrar uso en la tabla de uso
        global $wpdb;
        $db = MHTP_TS_DB::get_instance();
        $table = $db->get_usage_table();
        
        $wpdb->insert(
            $table,
            array(
                'session_id' => $this->id,
                'user_id' => $this->user_id,
                'used_at' => $used_at
            ),
            array('%d', '%d', '%s')
        );
        
        return true;
    }

    /**
     * Marcar sesión como expirada
     */
    public function mark_as_expired() {
        $this->status = 'expired';
        return $this->save();
    }

    /**
     * Obtener sesión por ID
     */
    public static function get_by_id($session_id) {
        global $wpdb;
        $db = MHTP_TS_DB::get_instance();
        $table = $db->get_sessions_table();
        
        $session_data = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE id = %d",
                $session_id
            ),
            ARRAY_A
        );
        
        if ($session_data) {
            return new self($session_data);
        }
        
        return null;
    }
}
