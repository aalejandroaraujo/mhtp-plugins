<?php
/**
 * Plugin Name: MHTP Expert List V2
 * Description: Plugin para mostrar la lista de expertos en la plataforma Mental Health Triage Platform
 * Version: 2.0.0
 * Author: Araujo Innovations
 * Author URI: https://araujoinnovations.com
 * Text Domain: mhtp-expert-list-v2
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class MHTP_Expert_List_V2 {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Registrar shortcode
        add_shortcode('mhtp_expert_list', array($this, 'render_expert_list'));
        
        // Registrar estilos y scripts
        add_action('wp_enqueue_scripts', array($this, 'register_scripts'));
        
        // Añadir página de configuración en el admin
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Registrar AJAX handlers
        add_action('wp_ajax_mhtp_check_available_sessions', array($this, 'ajax_check_available_sessions'));
        add_action('wp_ajax_nopriv_mhtp_check_available_sessions', array($this, 'ajax_check_available_sessions'));
        
        // Añadir filtro para limpiar caché
        add_filter('script_loader_tag', array($this, 'add_cache_busting_query'), 10, 2);
        add_filter('style_loader_tag', array($this, 'add_cache_busting_query'), 10, 2);
    }
    
    /**
     * Añadir parámetro para evitar caché
     */
    public function add_cache_busting_query($tag, $handle) {
        if (strpos($handle, 'mhtp-expert-list') !== false) {
            return str_replace('.js', '.js?v=' . time(), str_replace('.css', '.css?v=' . time(), $tag));
        }
        return $tag;
    }
    
    /**
     * Registrar estilos y scripts
     */
    public function register_scripts() {
        // Registrar y encolar CSS
        wp_register_style(
            'mhtp-expert-list-v2-css',
            plugin_dir_url(__FILE__) . 'css/expert-list.css',
            array(),
            '2.0.0.' . time() // Añadir timestamp para evitar caché
        );
        
        // Registrar y encolar JS
        wp_register_script(
            'mhtp-expert-list-v2-js',
            plugin_dir_url(__FILE__) . 'js/expert-list.js',
            array('jquery'),
            '2.0.0.' . time(), // Añadir timestamp para evitar caché
            true
        );
        
        // Pasar variables a JavaScript
        wp_localize_script(
            'mhtp-expert-list-v2-js',
            'mhtp_expert_list_vars',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mhtp_expert_list_nonce'),
                'i18n' => array(
                    'all_specialties' => __('Todas las especialidades', 'mhtp-expert-list-v2'),
                    'search_placeholder' => __('Buscar experto...', 'mhtp-expert-list-v2'),
                    'no_experts_found' => __('No se encontraron expertos disponibles.', 'mhtp-expert-list-v2'),
                    'view_details' => __('Ver detalles', 'mhtp-expert-list-v2'),
                    'start_consultation' => __('Comenzar consulta', 'mhtp-expert-list-v2')
                ),
                'timestamp' => time() // Añadir timestamp para evitar caché
            )
        );
    }
    
    /**
     * Añadir página de configuración en el admin
     */
    public function add_admin_menu() {
        add_submenu_page(
            'options-general.php',
            'Configuración de MHTP Expert List',
            'MHTP Expert List',
            'manage_options',
            'mhtp-expert-list-v2',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Renderizar página de administración
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1>Configuración de MHTP Expert List</h1>
            <p>Plugin desarrollado por Araujo Innovations</p>
            <form method="post" action="options.php">
                <?php
                settings_fields('mhtp_expert_list_options');
                do_settings_sections('mhtp_expert_list');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Verificar si el usuario tiene sesiones disponibles (AJAX)
     */
    public function ajax_check_available_sessions() {
        // Verificar nonce
        check_ajax_referer('mhtp_expert_list_nonce', 'nonce');
        
        $has_sessions = false;
        
        // Verificar si el usuario está logueado
        if (is_user_logged_in()) {
            // Aquí iría la lógica para verificar si el usuario tiene sesiones disponibles
            // Por ahora, simplemente devolvemos true para todos los usuarios logueados
            $has_sessions = true;
        }
        
        wp_send_json_success(array(
            'has_sessions' => $has_sessions
        ));
    }
    
    /**
     * Renderizar lista de expertos
     */
    public function render_expert_list($atts) {
        // Encolar estilos y scripts
        wp_enqueue_style('mhtp-expert-list-v2-css');
        wp_enqueue_script('mhtp-expert-list-v2-js');
        
        // Procesar atributos
        $atts = shortcode_atts(
            array(
                'category' => 'expertos',
                'limit' => -1,
                'orderby' => 'title',
                'order' => 'ASC',
                'show_search' => 'yes',
                'show_filters' => 'yes'
            ),
            $atts,
            'mhtp_expert_list'
        );
        
        // Convertir valores de string a booleanos
        $show_search = $atts['show_search'] === 'yes';
        $show_filters = $atts['show_filters'] === 'yes';
        
        // Obtener productos de la categoría "Expertos"
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => $atts['limit'],
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => $atts['category'],
                    'operator' => 'IN',
                ),
            ),
        );
        
        $products = new WP_Query($args);
        
        // Iniciar buffer de salida
        ob_start();
        
        if ($products->have_posts()) {
            ?>
            <div class="mhtp-expert-list-container">
                <?php if ($show_search) : ?>
                <div class="mhtp-expert-search">
                    <input type="text" placeholder="<?php esc_attr_e('Buscar experto...', 'mhtp-expert-list-v2'); ?>" class="mhtp-expert-search-input">
                </div>
                <?php endif; ?>
                
                <?php if ($show_filters) : ?>
                <div class="mhtp-expert-filters">
                    <?php
                    // Recopilar etiquetas únicas de los expertos
                    $tags = array();
                    while ($products->have_posts()) {
                        $products->the_post();
                        global $product;
                        
                        // Obtener etiquetas del producto
                        $product_tags = get_the_terms(get_the_ID(), 'product_tag');
                        
                        if ($product_tags && !is_wp_error($product_tags)) {
                            foreach ($product_tags as $tag) {
                                if (!isset($tags[$tag->slug])) {
                                    $tags[$tag->slug] = $tag->name;
                                }
                            }
                        }
                    }
                    // Reiniciar el loop
                    $products->rewind_posts();
                    ?>
                    
                    <select class="mhtp-expert-filter-select">
                        <option value="all"><?php esc_html_e('Todas las especialidades', 'mhtp-expert-list-v2'); ?></option>
                        <?php foreach ($tags as $slug => $name) : ?>
                            <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="mhtp-expert-list-grid">
                    <?php
                    while ($products->have_posts()) {
                        $products->the_post();
                        global $product;
                        
                        // Obtener datos del producto
                        $product_id = get_the_ID();
                        $title = get_the_title();
                        $short_description = get_the_excerpt();
                        $permalink = get_permalink();
                        $image = get_the_post_thumbnail_url($product_id, 'medium');
                        
                        // Obtener etiquetas del producto para filtrado por JavaScript
                        $product_tags = get_the_terms($product_id, 'product_tag');
                        $tag_classes = '';
                        $tag_data = '';
                        
                        if ($product_tags && !is_wp_error($product_tags)) {
                            $tag_slugs = array();
                            $tag_names = array();
                            
                            foreach ($product_tags as $tag) {
                                $tag_slugs[] = $tag->slug;
                                $tag_names[] = $tag->name;
                            }
                            
                            $tag_classes = implode(' ', $tag_slugs);
                            $tag_data = 'data-tags="' . esc_attr(implode(',', $tag_slugs)) . '"';
                        }
                        
                        // Si no hay imagen, usar una imagen por defecto
                        if (!$image) {
                            $image = plugin_dir_url(__FILE__) . 'img/default-expert.jpg';
                        }
                        
                        ?>
                        <div class="mhtp-expert-card <?php echo esc_attr($tag_classes); ?>" <?php echo $tag_data; ?>>
                            <div class="mhtp-expert-image">
                                <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($title); ?>">
                            </div>
                            <div class="mhtp-expert-content">
                                <h3 class="mhtp-expert-title"><?php echo esc_html($title); ?></h3>
                                <div class="mhtp-expert-description">
                                    <?php echo wp_kses_post($short_description); ?>
                                </div>
                                <div class="mhtp-expert-actions">
                                    <a href="<?php echo esc_url($permalink); ?>" class="mhtp-expert-details-btn"><?php esc_html_e('Ver detalles', 'mhtp-expert-list-v2'); ?></a>
                                    <a href="<?php echo esc_url(home_url('/chat-con-expertos/?expert_id=' . $product_id)); ?>" class="mhtp-expert-consult-btn"><?php esc_html_e('Comenzar consulta', 'mhtp-expert-list-v2'); ?></a>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
            <?php
        } else {
            ?>
            <div class="mhtp-expert-list-empty">
                <p><?php esc_html_e('No se encontraron expertos disponibles.', 'mhtp-expert-list-v2'); ?></p>
            </div>
            <?php
        }
        
        // Restaurar datos originales
        wp_reset_postdata();
        
        // Devolver contenido
        return ob_get_clean();
    }
}

// Inicializar plugin
$mhtp_expert_list_v2 = new MHTP_Expert_List_V2();
