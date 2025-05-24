<?php
/**
 * Clase para gestionar la interfaz pública del plugin
 *
 * @package MHTP_Test_Sessions
 */

// Si este archivo es llamado directamente, abortar
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar la interfaz pública
 */
class MHTP_TS_Public {

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
        // Registrar estilos y scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
        
        // Añadir shortcode para mostrar sesiones
        add_shortcode('mhtp_user_sessions', array($this, 'user_sessions_shortcode'));
        
        // Añadir filtro para mostrar sesiones en el perfil de usuario
        add_action('woocommerce_account_dashboard', array($this, 'show_sessions_in_dashboard'));
    }

    /**
     * Cargar estilos y scripts públicos
     */
    public function enqueue_public_assets() {
        // Solo cargar en páginas relevantes
        if (is_account_page() || has_shortcode(get_post()->post_content, 'mhtp_user_sessions')) {
            // Estilos
            wp_enqueue_style(
                'mhtp-ts-public-css',
                MHTP_TS_PLUGIN_URL . 'assets/css/public.css',
                array(),
                MHTP_TS_VERSION
            );
            
            // Scripts
            wp_enqueue_script(
                'mhtp-ts-public-js',
                MHTP_TS_PLUGIN_URL . 'assets/js/public.js',
                array('jquery'),
                MHTP_TS_VERSION,
                true
            );
            
            // Localización para scripts
            wp_localize_script(
                'mhtp-ts-public-js',
                'mhtp_ts_public',
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('mhtp_ts_public_nonce')
                )
            );
        }
    }

    /**
     * Shortcode para mostrar sesiones de usuario
     */
    public function user_sessions_shortcode($atts) {
        // Si el usuario no está logueado, mostrar mensaje
        if (!is_user_logged_in()) {
            return '<div class="mhtp-ts-not-logged-in">' . 
                __('Debes iniciar sesión para ver tus sesiones disponibles.', 'mhtp-test-sessions') . 
                '</div>';
        }
        
        // Obtener usuario actual
        $user_id = get_current_user_id();
        
        // Obtener sesiones disponibles
        $db = MHTP_TS_DB::get_instance();
        $test_sessions = $db->get_user_test_sessions($user_id);
        
        // Obtener total de sesiones disponibles
        $total_available = get_user_meta($user_id, 'mhtp_available_sessions', true);
        
        if (empty($total_available)) {
            $total_available = 0;
        }
        
        // Iniciar buffer de salida
        ob_start();
        
        // Incluir template
        include MHTP_TS_PLUGIN_DIR . 'public/templates/user-sessions.php';
        
        // Devolver contenido
        return ob_get_clean();
    }

    /**
     * Mostrar sesiones en el dashboard de WooCommerce
     */
    public function show_sessions_in_dashboard() {
        // Obtener usuario actual
        $user_id = get_current_user_id();
        
        // Obtener total de sesiones disponibles
        $total_available = get_user_meta($user_id, 'mhtp_available_sessions', true);
        
        if (empty($total_available)) {
            $total_available = 0;
        }
        
        // Mostrar información de sesiones
        ?>
        <div class="mhtp-ts-dashboard-sessions">
            <h3><?php _e('Mis Sesiones de Consulta', 'mhtp-test-sessions'); ?></h3>
            
            <p>
                <?php 
                printf(
                    __('Tienes <strong>%d</strong> sesiones disponibles. <a href="%s">Ver detalles</a>', 'mhtp-test-sessions'),
                    $total_available,
                    wc_get_endpoint_url('view-sessions', '', wc_get_page_permalink('myaccount'))
                ); 
                ?>
            </p>
            
            <?php if ($total_available <= 0): ?>
                <p>
                    <a href="<?php echo esc_url(get_permalink(wc_get_page_id('shop'))); ?>" class="button">
                        <?php _e('Adquirir Sesiones', 'mhtp-test-sessions'); ?>
                    </a>
                </p>
            <?php endif; ?>
        </div>
        <?php
    }
}
