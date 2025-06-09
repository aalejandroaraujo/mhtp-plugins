<?php
/**
 * Typebot settings page for MHTP Chat Interface
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MHTP_Typebot_Settings {

    private $params = array( 'ExpertId', 'ExpertName', 'Topic', 'HistoryEnabled', 'IsClient' );

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function add_admin_menu() {
        add_options_page(
            __( 'Typebot Configuration', 'mhtp-chat-interface' ),
            __( 'Typebot Configuration', 'mhtp-chat-interface' ),
            'manage_options',
            'mhtp-typebot-config',
            array( $this, 'settings_page' )
        );
    }

    public function register_settings() {
        register_setting( 'mhtp_typebot_config', 'mhtp_typebot_options', array( $this, 'sanitize' ) );

        add_settings_section(
            'mhtp_typebot_section',
            __( 'Typebot Configuration', 'mhtp-chat-interface' ),
            '__return_false',
            'mhtp-typebot-config'
        );

        add_settings_field(
            'chatbot_url',
            __( 'Chatbot URL', 'mhtp-chat-interface' ),
            array( $this, 'chatbot_url_render' ),
            'mhtp-typebot-config',
            'mhtp_typebot_section'
        );

        add_settings_field(
            'param_count',
            __( 'Number of parameters', 'mhtp-chat-interface' ),
            array( $this, 'param_count_render' ),
            'mhtp-typebot-config',
            'mhtp_typebot_section'
        );

        add_settings_field(
            'selected_params',
            __( 'Parameters to pass', 'mhtp-chat-interface' ),
            array( $this, 'selected_params_render' ),
            'mhtp-typebot-config',
            'mhtp_typebot_section'
        );

        add_settings_field(
            'param_values',
            __( 'Parameter values', 'mhtp-chat-interface' ),
            array( $this, 'param_values_render' ),
            'mhtp-typebot-config',
            'mhtp_typebot_section'
        );
    }

    public function chatbot_url_render() {
        $options = get_option( 'mhtp_typebot_options' );
        $url     = isset( $options['chatbot_url'] ) ? esc_url( $options['chatbot_url'] ) : '';
        echo '<input type="url" name="mhtp_typebot_options[chatbot_url]" value="' . esc_attr( $url ) . '" class="regular-text" required>';
    }

    public function param_count_render() {
        $options = get_option( 'mhtp_typebot_options' );
        $count   = isset( $options['param_count'] ) ? intval( $options['param_count'] ) : 1;
        echo '<input id="param_count" type="number" name="mhtp_typebot_options[param_count]" value="' . esc_attr( $count ) . '" min="1" max="5" required>';
    }

    public function selected_params_render() {
        $options   = get_option( 'mhtp_typebot_options' );
        $selected  = isset( $options['selected_params'] ) && is_array( $options['selected_params'] ) ? $options['selected_params'] : array();
        foreach ( $this->params as $param ) {
            $checked = in_array( $param, $selected, true ) ? 'checked' : '';
            echo '<label style="display:block;"><input type="checkbox" name="mhtp_typebot_options[selected_params][]" value="' . esc_attr( $param ) . '" ' . $checked . '> ' . esc_html( $param ) . '</label>';
        }
        echo '<p id="param_count_notice" style="color:red;"></p>';
    }

    public function param_values_render() {
        $options  = get_option( 'mhtp_typebot_options' );
        $selected = isset( $options['selected_params'] ) && is_array( $options['selected_params'] ) ? $options['selected_params'] : array();
        $values   = isset( $options['param_values'] ) && is_array( $options['param_values'] ) ? $options['param_values'] : array();
        foreach ( $this->params as $param ) {
            $style = in_array( $param, $selected, true ) ? '' : 'display:none;';
            $val   = isset( $values[ $param ] ) ? $values[ $param ] : '';
            printf(
                '<div class="mhtp-param-value" data-param="%1$s" style="%2$s"><label>%1$s&nbsp;%3$s</label><br><input type="text" name="mhtp_typebot_options[param_values][%1$s]" value="%4$s" class="regular-text"></div>',
                esc_attr( $param ),
                esc_attr( $style ),
                esc_html__( 'value', 'mhtp-chat-interface' ),
                esc_attr( $val )
            );
        }
        ?>
        <script>
        jQuery(document).ready(function($){
            function toggleValues(){
                $('div.mhtp-param-value').each(function(){
                    var param = $(this).data('param');
                    var checked = $('input[name="mhtp_typebot_options[selected_params][]"][value="'+param+'"]').prop('checked');
                    $(this).css('display', checked ? 'block' : 'none');
                });
            }
            $('input[name="mhtp_typebot_options[selected_params][]"]').on('change', toggleValues);
            toggleValues();
        });
        </script>
        <?php
    }

    public function sanitize( $input ) {
        $output                  = array();
        $output['chatbot_url']   = isset( $input['chatbot_url'] ) ? esc_url_raw( $input['chatbot_url'] ) : '';
        $output['param_count']   = isset( $input['param_count'] ) ? max( 1, min( 5, intval( $input['param_count'] ) ) ) : 1;
        $output['selected_params'] = array();
        if ( ! empty( $input['selected_params'] ) && is_array( $input['selected_params'] ) ) {
            foreach ( $input['selected_params'] as $param ) {
                if ( in_array( $param, $this->params, true ) ) {
                    $output['selected_params'][] = sanitize_text_field( $param );
                }
            }
        }
        $output['param_values'] = array();
        if ( ! empty( $input['param_values'] ) && is_array( $input['param_values'] ) ) {
            foreach ( $input['param_values'] as $key => $val ) {
                if ( in_array( $key, $this->params, true ) ) {
                    $output['param_values'][ $key ] = sanitize_text_field( $val );
                }
            }
        }

        if ( count( $output['selected_params'] ) !== $output['param_count'] ) {
            add_settings_error( 'mhtp_typebot_options', 'param_mismatch', __( 'Selected parameters must match the number of parameters.', 'mhtp-chat-interface' ) );
        }

        return $output;
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Typebot Configuration', 'mhtp-chat-interface' ); ?></h1>
            <p><?php esc_html_e( 'Configure your Typebot embed. Enter the full URL or slug for your bot. Select the parameters you wish to pass and specify a value for each one. You may use placeholders like {ExpertId} which will be replaced dynamically on the chat page.', 'mhtp-chat-interface' ); ?></p>
            <form action="options.php" method="post">
                <?php
                settings_fields( 'mhtp_typebot_config' );
                do_settings_sections( 'mhtp-typebot-config' );
                submit_button();
                ?>
            </form>
        </div>
        <script>
        jQuery(document).ready(function($){
            function validate(){
                var count = parseInt($('#param_count').val()) || 0;
                var selected = $('input[name="mhtp_typebot_options[selected_params][]"]:checked').length;
                if(selected !== count){
                    $('#param_count_notice').text('<?php echo esc_js( __( 'Parameter count mismatch.', 'mhtp-chat-interface' ) ); ?>');
                }else{
                    $('#param_count_notice').text('');
                }
            }
            $('#param_count').on('input change', validate);
            $('input[name="mhtp_typebot_options[selected_params][]"]').on('change', validate);
            validate();
        });
        </script>
        <?php
    }
}

new MHTP_Typebot_Settings();

