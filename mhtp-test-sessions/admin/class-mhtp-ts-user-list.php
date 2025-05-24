<?php
/**
 * Clase para gestionar la lista de usuarios
 *
 * @package MHTP_Test_Sessions
 */

// Si este archivo es llamado directamente, abortar
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar la lista de usuarios
 */
class MHTP_TS_User_List {

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
        add_action('wp_ajax_mhtp_ts_get_user_sessions_modal', array($this, 'ajax_get_user_sessions_modal'));
    }

    /**
     * Renderizar página de lista de usuarios
     */
    public function render_user_list_page() {
        global $wp_roles;
        
        // Parámetros de paginación
        $page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        
        // Obtener total de usuarios
        $total_users = count_users();
        $total_pages = ceil($total_users['total_users'] / $per_page);
        
        // Obtener usuarios para esta página
        $users = get_users(array(
            'number' => $per_page,
            'offset' => $offset,
            'orderby' => 'display_name',
            'order' => 'ASC'
        ));
        
        // Generar paginación
        $pagination = $this->generate_pagination($page, $total_pages);
        
        // Obtener roles
        $roles = wp_roles()->get_names();
        
        // Obtener categorías disponibles
        $admin = MHTP_TS_Admin::get_instance();
        $categories = $admin->get_available_categories();
        
        // Incluir template
        include MHTP_TS_PLUGIN_DIR . 'admin/templates/user-list-page.php';
    }

    /**
     * Generar HTML de paginación
     */
    private function generate_pagination($current_page, $total_pages) {
        if ($total_pages <= 1) {
            return '';
        }
        
        $pagination = '<div class="tablenav-pages">';
        $pagination .= '<span class="displaying-num">' . sprintf(_n('%s elemento', '%s elementos', $total_pages, 'mhtp-test-sessions'), number_format_i18n($total_pages)) . '</span>';
        
        $pagination .= '<span class="pagination-links">';
        
        // Primera página
        if ($current_page > 1) {
            $pagination .= '<a class="first-page button" href="' . esc_url(add_query_arg('paged', 1)) . '"><span class="screen-reader-text">' . __('Primera página', 'mhtp-test-sessions') . '</span><span aria-hidden="true">&laquo;</span></a>';
        } else {
            $pagination .= '<span class="first-page button disabled"><span class="screen-reader-text">' . __('Primera página', 'mhtp-test-sessions') . '</span><span aria-hidden="true">&laquo;</span></span>';
        }
        
        // Página anterior
        if ($current_page > 1) {
            $pagination .= '<a class="prev-page button" href="' . esc_url(add_query_arg('paged', $current_page - 1)) . '"><span class="screen-reader-text">' . __('Página anterior', 'mhtp-test-sessions') . '</span><span aria-hidden="true">&lsaquo;</span></a>';
        } else {
            $pagination .= '<span class="prev-page button disabled"><span class="screen-reader-text">' . __('Página anterior', 'mhtp-test-sessions') . '</span><span aria-hidden="true">&lsaquo;</span></span>';
        }
        
        // Número de página actual
        $pagination .= '<span class="paging-input">';
        $pagination .= '<span class="tablenav-paging-text">' . $current_page . ' ' . __('de', 'mhtp-test-sessions') . ' <span class="total-pages">' . $total_pages . '</span></span>';
        $pagination .= '</span>';
        
        // Página siguiente
        if ($current_page < $total_pages) {
            $pagination .= '<a class="next-page button" href="' . esc_url(add_query_arg('paged', $current_page + 1)) . '"><span class="screen-reader-text">' . __('Página siguiente', 'mhtp-test-sessions') . '</span><span aria-hidden="true">&rsaquo;</span></a>';
        } else {
            $pagination .= '<span class="next-page button disabled"><span class="screen-reader-text">' . __('Página siguiente', 'mhtp-test-sessions') . '</span><span aria-hidden="true">&rsaquo;</span></span>';
        }
        
        // Última página
        if ($current_page < $total_pages) {
            $pagination .= '<a class="last-page button" href="' . esc_url(add_query_arg('paged', $total_pages)) . '"><span class="screen-reader-text">' . __('Última página', 'mhtp-test-sessions') . '</span><span aria-hidden="true">&raquo;</span></a>';
        } else {
            $pagination .= '<span class="last-page button disabled"><span class="screen-reader-text">' . __('Última página', 'mhtp-test-sessions') . '</span><span aria-hidden="true">&raquo;</span></span>';
        }
        
        $pagination .= '</span>';
        $pagination .= '</div>';
        
        return $pagination;
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
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $role = isset($_POST['role']) ? sanitize_text_field($_POST['role']) : '';
        $has_sessions = isset($_POST['has_sessions']) ? sanitize_text_field($_POST['has_sessions']) : '';
        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        
        // Construir argumentos para get_users
        $args = array(
            'number' => $per_page,
            'offset' => $offset,
            'orderby' => 'display_name',
            'order' => 'ASC'
        );
        
        // Filtrar por búsqueda
        if (!empty($search)) {
            $args['search'] = '*' . $search . '*';
            $args['search_columns'] = array('user_login', 'user_email', 'display_name');
        }
        
        // Filtrar por rol
        if (!empty($role)) {
            $args['role'] = $role;
        }
        
        // Obtener usuarios
        $users = get_users($args);
        
        // Filtrar por sesiones si es necesario
        if ($has_sessions !== '') {
            $filtered_users = array();
            
            foreach ($users as $user) {
                $sessions_count = get_user_meta($user->ID, 'mhtp_available_sessions', true);
                
                if (empty($sessions_count)) {
                    $sessions_count = 0;
                }
                
                if (($has_sessions === '1' && $sessions_count > 0) || ($has_sessions === '0' && $sessions_count <= 0)) {
                    $filtered_users[] = $user;
                }
            }
            
            $users = $filtered_users;
        }
        
        // Preparar datos para respuesta
        $response_data = array();
        global $wp_roles;
        
        foreach ($users as $user) {
            $user_data = get_userdata($user->ID);
            $roles = $user_data->roles;
            $role_names = array();
            
            foreach ($roles as $role) {
                if (isset($wp_roles->role_names[$role])) {
                    $role_names[] = translate_user_role($wp_roles->role_names[$role]);
                }
            }
            
            $sessions_count = get_user_meta($user->ID, 'mhtp_available_sessions', true);
            if (empty($sessions_count)) {
                $sessions_count = 0;
            }
            
            $response_data[] = array(
                'id' => $user->ID,
                'display_name' => $user->display_name,
                'email' => $user->user_email,
                'roles' => implode(', ', $role_names),
                'registered' => date_i18n(get_option('date_format'), strtotime($user->user_registered)),
                'sessions_count' => $sessions_count
            );
        }
        
        // Obtener total de usuarios para paginación
        $total_args = $args;
        unset($total_args['number']);
        unset($total_args['offset']);
        $total_users = count(get_users($total_args));
        
        if ($has_sessions !== '') {
            // Si filtramos por sesiones, el total es el número de usuarios filtrados
            $total_users = count($response_data);
        }
        
        $total_pages = ceil($total_users / $per_page);
        
        // Generar paginación
        $pagination = $this->generate_pagination($page, $total_pages);
        
        wp_send_json_success(array(
            'users' => $response_data,
            'pagination' => $pagination,
            'total' => $total_users,
            'page' => $page,
            'total_pages' => $total_pages
        ));
    }

    /**
     * AJAX: Obtener sesiones de usuario para modal
     */
    public function ajax_get_user_sessions_modal() {
        // Verificar nonce
        check_ajax_referer('mhtp_ts_admin_nonce', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('manage_test_sessions')) {
            wp_send_json_error(array('message' => __('No tienes permisos para realizar esta acción', 'mhtp-test-sessions')));
        }
        
        // Obtener y validar datos
        $user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
        
        if ($user_id <= 0) {
            wp_send_json_error(array('message' => __('Usuario inválido', 'mhtp-test-sessions')));
        }
        
        // Obtener sesiones
        $db = MHTP_TS_DB::get_instance();
        $sessions = $db->get_user_test_sessions($user_id);
        
        // Obtener datos de usuario
        $user = get_userdata($user_id);
        
        wp_send_json_success(array(
            'sessions' => $sessions,
            'user' => array(
                'display_name' => $user->display_name,
                'email' => $user->user_email
            )
        ));
    }
}
