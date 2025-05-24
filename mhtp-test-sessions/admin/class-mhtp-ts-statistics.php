<?php
/**
 * Clase para gestionar estadísticas de uso de sesiones
 *
 * @package MHTP_Test_Sessions
 */

// Si este archivo es llamado directamente, abortar
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar estadísticas de uso de sesiones
 */
class MHTP_TS_Statistics {

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
        add_action('wp_ajax_mhtp_ts_get_statistics', array($this, 'ajax_get_statistics'));
        add_action('wp_ajax_mhtp_ts_export_statistics', array($this, 'ajax_export_statistics'));
    }

    /**
     * AJAX: Obtener estadísticas
     */
    public function ajax_get_statistics() {
        // Verificar nonce
        check_ajax_referer('mhtp_ts_admin_nonce', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('view_test_sessions_stats')) {
            wp_send_json_error(array('message' => __('No tienes permisos para realizar esta acción', 'mhtp-test-sessions')));
        }
        
        // Obtener y validar datos
        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : 'month';
        
        // Obtener estadísticas
        $db = MHTP_TS_DB::get_instance();
        $stats = $db->get_usage_statistics($period);
        
        // Obtener datos para gráficos
        $chart_data = $this->prepare_chart_data($period);
        
        wp_send_json_success(array(
            'stats' => $stats,
            'chart_data' => $chart_data
        ));
    }

    /**
     * AJAX: Exportar estadísticas
     */
    public function ajax_export_statistics() {
        // Verificar nonce
        check_ajax_referer('mhtp_ts_admin_nonce', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('view_test_sessions_stats')) {
            wp_send_json_error(array('message' => __('No tienes permisos para realizar esta acción', 'mhtp-test-sessions')));
        }
        
        // Obtener y validar datos
        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : 'month';
        
        // Obtener estadísticas
        $db = MHTP_TS_DB::get_instance();
        $stats = $db->get_usage_statistics($period);
        
        // Preparar datos para CSV
        $csv_data = array(
            array(__('Estadísticas de Uso de Sesiones de Prueba', 'mhtp-test-sessions')),
            array(__('Período', 'mhtp-test-sessions'), $this->get_period_label($period)),
            array(__('Fecha de Exportación', 'mhtp-test-sessions'), date_i18n(get_option('date_format'))),
            array(''),
            array(__('Métricas Generales', 'mhtp-test-sessions')),
            array(__('Total de Sesiones Añadidas', 'mhtp-test-sessions'), $stats['total_sessions_added']),
            array(__('Total de Sesiones Usadas', 'mhtp-test-sessions'), $stats['total_sessions_used']),
            array(__('Total de Sesiones Caducadas', 'mhtp-test-sessions'), $stats['total_sessions_expired']),
            array(__('Total de Usuarios con Sesiones', 'mhtp-test-sessions'), $stats['total_users_with_sessions']),
            array(__('Tasa de Conversión', 'mhtp-test-sessions'), number_format($stats['conversion_rate'], 2) . '%'),
            array(''),
            array(__('Uso por Categoría', 'mhtp-test-sessions'))
        );
        
        // Añadir datos de categorías
        foreach ($stats['usage_by_category'] as $category => $count) {
            $csv_data[] = array($category, $count);
        }
        
        // Generar CSV
        $filename = 'mhtp-test-sessions-stats-' . date('Y-m-d') . '.csv';
        $csv_content = '';
        
        foreach ($csv_data as $row) {
            $csv_content .= implode(',', $row) . "\n";
        }
        
        // Enviar respuesta con URL para descargar
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['basedir'] . '/' . $filename;
        $file_url = $upload_dir['baseurl'] . '/' . $filename;
        
        file_put_contents($file_path, $csv_content);
        
        wp_send_json_success(array(
            'file_url' => $file_url,
            'filename' => $filename
        ));
    }

    /**
     * Preparar datos para gráficos
     */
    private function prepare_chart_data($period) {
        global $wpdb;
        $db = MHTP_TS_DB::get_instance();
        $sessions_table = $db->get_sessions_table();
        $usage_table = $db->get_usage_table();
        
        // Determinar fecha de inicio según el período
        $start_date = '';
        $group_by = '';
        $date_format = '';
        
        switch ($period) {
            case 'week':
                $start_date = date('Y-m-d', strtotime('-7 days'));
                $group_by = 'DAY(date_column)';
                $date_format = '%Y-%m-%d';
                break;
            case 'month':
                $start_date = date('Y-m-d', strtotime('-30 days'));
                $group_by = 'DAY(date_column)';
                $date_format = '%Y-%m-%d';
                break;
            case 'year':
                $start_date = date('Y-m-d', strtotime('-1 year'));
                $group_by = 'MONTH(date_column)';
                $date_format = '%Y-%m';
                break;
            default:
                $start_date = date('Y-m-d', strtotime('-30 days'));
                $group_by = 'DAY(date_column)';
                $date_format = '%Y-%m-%d';
        }
        
        // Datos de sesiones añadidas por día/mes
        $sessions_added = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE_FORMAT(created_at, %s) as date, SUM(quantity) as count 
            FROM $sessions_table 
            WHERE created_at >= %s 
            GROUP BY DATE_FORMAT(created_at, %s) 
            ORDER BY created_at ASC",
            $date_format,
            $start_date,
            $date_format
        ));
        
        // Datos de sesiones usadas por día/mes
        $sessions_used = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE_FORMAT(used_at, %s) as date, COUNT(*) as count 
            FROM $usage_table 
            WHERE used_at >= %s 
            GROUP BY DATE_FORMAT(used_at, %s) 
            ORDER BY used_at ASC",
            $date_format,
            $start_date,
            $date_format
        ));
        
        // Preparar datos para gráficos
        $chart_data = array(
            'labels' => array(),
            'datasets' => array(
                array(
                    'label' => __('Sesiones Añadidas', 'mhtp-test-sessions'),
                    'data' => array(),
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 1
                ),
                array(
                    'label' => __('Sesiones Usadas', 'mhtp-test-sessions'),
                    'data' => array(),
                    'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                    'borderColor' => 'rgba(255, 99, 132, 1)',
                    'borderWidth' => 1
                )
            )
        );
        
        // Generar fechas para el período
        $dates = array();
        $current_date = new DateTime($start_date);
        $end_date = new DateTime();
        
        while ($current_date <= $end_date) {
            $date_key = $current_date->format($period === 'year' ? 'Y-m' : 'Y-m-d');
            $dates[$date_key] = 0;
            
            if ($period === 'year') {
                $current_date->modify('+1 month');
            } else {
                $current_date->modify('+1 day');
            }
        }
        
        // Rellenar datos de sesiones añadidas
        $added_data = array();
        foreach ($sessions_added as $row) {
            $added_data[$row->date] = (int) $row->count;
        }
        
        // Rellenar datos de sesiones usadas
        $used_data = array();
        foreach ($sessions_used as $row) {
            $used_data[$row->date] = (int) $row->count;
        }
        
        // Combinar datos
        foreach ($dates as $date => $dummy) {
            $chart_data['labels'][] = $date;
            $chart_data['datasets'][0]['data'][] = isset($added_data[$date]) ? $added_data[$date] : 0;
            $chart_data['datasets'][1]['data'][] = isset($used_data[$date]) ? $used_data[$date] : 0;
        }
        
        return $chart_data;
    }

    /**
     * Obtener etiqueta para el período
     */
    private function get_period_label($period) {
        switch ($period) {
            case 'week':
                return __('Última Semana', 'mhtp-test-sessions');
            case 'month':
                return __('Último Mes', 'mhtp-test-sessions');
            case 'year':
                return __('Último Año', 'mhtp-test-sessions');
            default:
                return __('Último Mes', 'mhtp-test-sessions');
        }
    }
}
